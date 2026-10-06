<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\DayReport;
use App\Models\Holiday;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Store;
use App\Models\Target;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use App\Support\Clock;
use Carbon\CarbonImmutable;

/**
 * Builds the data a page needs for the logged-in user, limited to what they may see,
 * in the same shape as the demo's local data (so all page calculations work unchanged).
 * Window: first day of the previous month until today.
 */
class StateBuilder
{
    public static function build(User $me): array
    {
        $today = Clock::today();
        $from = CarbonImmutable::parse($today)->startOfMonth()->subMonth()->toDateString();

        $teamIds = Access::teamIds($me);
        $teams = Team::whereIn('id', $teamIds)->orderBy('id')->get();
        $areaIds = $me->role === 'admin' ? Area::pluck('id')->all() : $teams->pluck('area_id')->push($me->area_id)->filter()->unique()->values()->all();

        // People: everyone in the visible teams + all staff (for "set by" / "added by" names)
        $people = User::whereIn('team_id', $teamIds)->orWhereIn('role', ['leader', 'supervisor', 'admin'])->orderBy('id')->get();
        $users = $people->map(fn (User $u) => Serializer::user($u, $u->id === $me->id || Access::canManageUser($me, $u)))->values()->all();

        // Whose sales: SPG → own (full) + teammates (daily totals only); Leader/Supervisor → team SPGs (full); Admin → none
        $spgIds = $people->where('role', 'spg')->pluck('id')->all();
        $fullIds = match ($me->role) {
            'spg' => [$me->id],
            'leader', 'supervisor' => $spgIds,
            default => [],
        };
        $totalsOnlyIds = $me->role === 'spg' ? array_values(array_diff($spgIds, [$me->id])) : [];
        $reportIds = array_merge($fullIds, $totalsOnlyIds);

        return [
            'version' => 'live',
            'me' => (string) $me->id,
            'mustChangePassword' => (bool) $me->must_change_password,
            'today' => $today,
            'from' => $from,
            'areas' => Area::whereIn('id', $areaIds)->get()->map(fn ($a) => ['id' => (string) $a->id, 'name' => $a->name])->all(),
            'teams' => $teams->map(fn ($t) => Serializer::team($t))->all(),
            'users' => $users,
            'products' => Product::orderBy('id')->get()->map(fn ($p) => Serializer::product($p))->all(),
            'stores' => Store::orderBy('id')->get()->map(fn ($s) => Serializer::store($s))->all(),
            'settings' => static::settings($teamIds),
            'targets' => static::targets($me->role === 'admin' ? [] : $spgIds),
            'reports' => static::reports($fullIds, $totalsOnlyIds, $from, $today),
            'shifts' => static::shifts($reportIds, $from, $today),
            'activity' => $me->isRole('admin', 'supervisor') ? static::activity() : [],
        ];
    }

    public static function settings(array $teamIds): array
    {
        $rows = TeamSetting::whereIn('team_id', $teamIds)->get()->keyBy('team_id');
        $holidays = Holiday::whereIn('team_id', $teamIds)->orderBy('date')->get()->groupBy('team_id');
        $out = [];
        foreach ($teamIds as $id) {
            $s = $rows[$id] ?? null;
            $d = TeamSetting::defaults();
            $out[(string) $id] = [
                'workingDays' => array_map('intval', $s->working_days ?? $d['working_days']),
                'editDays' => (int) ($s->edit_days ?? $d['edit_days']),
                'reminder' => $s->reminder_time ?? $d['reminder_time'],
                'holidays' => ($holidays[$id] ?? collect())->map(fn ($h) => ['date' => $h->date, 'name' => $h->name, 'working' => (bool) $h->is_working_day])->values()->all(),
            ];
        }

        return $out;
    }

    public static function targets(array $userIds): array
    {
        $out = [];
        foreach (Target::whereIn('user_id', $userIds)->get() as $t) {
            $out[$t->user_id.'|'.$t->month] = [
                'monthly' => (int) $t->monthly, 'weekly' => $t->weekly ? (int) $t->weekly : null, 'daily' => $t->daily ? (int) $t->daily : null,
                'setBy' => Serializer::id($t->set_by), 'setAt' => $t->set_at,
            ];
        }

        return $out;
    }

    public static function reports(array $fullIds, array $totalsOnlyIds, string $from, string $to): array
    {
        $out = [];
        $rows = Sale::query()->toBase()
            ->whereIn('user_id', array_merge($fullIds, $totalsOnlyIds))->whereNull('deleted_at')
            ->whereBetween('date', [$from, $to])->orderBy('date')->orderBy('time')->orderBy('id')
            ->get(['client_id', 'user_id', 'date', 'time', 'store_id', 'product_id', 'qty', 'price']);
        $full = array_flip($fullIds);
        foreach ($rows as $r) {
            $date = substr((string) $r->date, 0, 10);
            $key = $r->user_id.'|'.$date;
            if (! isset($out[$key])) {
                $out[$key] = ['userId' => (string) $r->user_id, 'date' => $date, 'items' => [], 'total' => 0, 'noSales' => false, 'unlocked' => false];
                if (isset($full[$r->user_id])) {
                    $out[$key]['transactions'] = [];
                }
            }
            $out[$key]['total'] += $r->qty * $r->price;
            if (isset($full[$r->user_id])) {
                $out[$key]['transactions'][] = [
                    'id' => $r->client_id, 'time' => $r->time ?? '', 'storeId' => Serializer::id($r->store_id),
                    'productId' => (string) $r->product_id, 'qty' => (int) $r->qty, 'price' => (int) $r->price,
                ];
                $ik = $r->product_id.'|'.$r->price;
                $out[$key]['_items'][$ik] = ['productId' => (string) $r->product_id, 'price' => (int) $r->price, 'qty' => ($out[$key]['_items'][$ik]['qty'] ?? 0) + (int) $r->qty];
            }
        }
        foreach (DayReport::whereIn('user_id', array_merge($fullIds, $totalsOnlyIds))->whereBetween('date', [$from, $to])->get() as $d) {
            $key = $d->user_id.'|'.$d->date;
            if (! isset($out[$key])) {
                if (! $d->no_sales) {
                    continue;
                }
                $out[$key] = ['userId' => (string) $d->user_id, 'date' => $d->date, 'items' => [], 'total' => 0, 'noSales' => false, 'unlocked' => false, 'transactions' => []];
            }
            $out[$key]['noSales'] = $d->no_sales && $out[$key]['total'] === 0;
            $out[$key]['unlocked'] = (bool) $d->unlocked;
        }
        foreach ($out as $k => $r) {
            $out[$k]['items'] = array_values($r['_items'] ?? []);
            unset($out[$k]['_items']);
        }

        return $out;
    }

    public static function shifts(array $userIds, string $from, string $to): array
    {
        $out = [];
        foreach (Shift::with('visits')->whereIn('user_id', $userIds)->whereBetween('date', [$from, $to])->get() as $s) {
            $out[$s->user_id.'|'.$s->date] = static::shift($s);
        }

        return $out;
    }

    public static function shift(Shift $s): array
    {
        return [
            'start' => $s->start_time, 'end' => $s->end_time, 'storeId' => Serializer::id($s->store_id),
            'visits' => $s->visits->map(fn ($v) => ['storeId' => (string) $v->store_id, 'from' => $v->from_time])->all(),
        ];
    }

    public static function activity(): array
    {
        return ActivityLog::latest('id')->limit(200)->get()->map(fn ($a) => [
            'at' => optional($a->created_at)->format('Y-m-d H:i'), 'by' => Serializer::id($a->user_id),
            'action' => $a->action, 'kind' => $a->kind, 'id' => $a->subject_id, 'name' => $a->name,
        ])->all();
    }
}
