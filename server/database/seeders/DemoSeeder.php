<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Loads the demo data (people, stores, products, targets, shifts and sales) exported from the
 * static demo by tools/export-demo-data.js. Run on an empty database: php artisan migrate:fresh --seed
 *
 * The data ends on the demo's "today" (2026-10-22). When the app's today (APP_DEMO_TODAY or the real date)
 * is later, all dates move forward by whole weeks so weekdays and Sundays stay the same.
 */
class DemoSeeder extends Seeder
{
    private array $ids = [];

    public function run(): void
    {
        $d = json_decode(file_get_contents(__DIR__.'/demo-data.json'), true);
        $shift = $this->offsetDays($d['today'], Clock::today());
        $move = fn (string $date) => $shift ? CarbonImmutable::parse($date)->addDays($shift)->toDateString() : $date;
        $now = now();
        $hash = array_map(fn ($pw) => Hash::make($pw), User::DEFAULT_PASSWORD);

        DB::transaction(function () use ($d, $move, $now, $hash) {
            foreach ($d['areas'] as $a) {
                $this->ids[$a['id']] = DB::table('areas')->insertGetId(['name' => $a['name'], 'created_at' => $now, 'updated_at' => $now]);
            }
            foreach ($d['teams'] as $t) {
                $this->ids[$t['id']] = DB::table('teams')->insertGetId(['name' => $t['name'], 'area_id' => $this->ids[$t['areaId']], 'created_at' => $now, 'updated_at' => $now]);
            }
            foreach ($d['users'] as $u) {
                $this->ids[$u['id']] = DB::table('users')->insertGetId([
                    'phone' => $u['phone'], 'name' => $u['name'], 'role' => $u['role'],
                    'area_id' => $this->map($u['areaId'] ?? null), 'team_id' => $this->map($u['teamId'] ?? null),
                    'photo_path' => $u['photo'] ?: null, 'password' => $hash[$u['role']], 'active' => $u['active'],
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            foreach ($d['teams'] as $t) {
                DB::table('teams')->where('id', $this->ids[$t['id']])->update(['leader_id' => $this->map($t['leaderId'])]);
            }
            foreach ($d['stores'] as $s) {
                $this->ids[$s['id']] = DB::table('stores')->insertGetId([
                    'name' => $s['name'], 'chain' => $s['chain'] ?: null, 'city' => $s['city'], 'address' => $s['address'] ?: null,
                    'area_id' => $this->map($s['areaId'] ?? null), 'active' => $s['active'],
                    'created_by' => $this->map($s['createdBy']), 'updated_by' => $this->map($s['updatedBy']), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            foreach ($d['users'] as $u) {
                if (! empty($u['homeStoreId'])) {
                    DB::table('users')->where('id', $this->ids[$u['id']])->update(['home_store_id' => $this->ids[$u['homeStoreId']]]);
                }
            }
            foreach ($d['products'] as $p) {
                $this->ids[$p['id']] = DB::table('products')->insertGetId([
                    'name' => $p['name'], 'sku' => $p['sku'] ?? null, 'price' => $p['price'], 'photo_path' => $p['image'] ?? null, 'active' => $p['active'],
                    'created_by' => $this->map($p['createdBy']), 'updated_by' => $this->map($p['updatedBy']), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            foreach ($d['settings'] as $teamId => $s) {
                DB::table('team_settings')->insert([
                    'team_id' => $this->ids[$teamId], 'working_days' => json_encode($s['workingDays']), 'edit_days' => $s['editDays'],
                    'reminder_time' => $s['reminder'], 'created_at' => $now, 'updated_at' => $now,
                ]);
                foreach ($s['holidays'] as $h) {
                    DB::table('holidays')->insert(['team_id' => $this->ids[$teamId], 'date' => $h['date'], 'name' => $h['name'], 'is_working_day' => $h['working']]);
                }
            }
            $this->targets($d['targets'], $move, $now);

            foreach ($d['shifts'] as $key => $s) {
                [$uid, $date] = explode('|', $key);
                $shiftId = DB::table('shifts')->insertGetId([
                    'user_id' => $this->ids[$uid], 'date' => $move($date), 'start_time' => $s['start'], 'end_time' => $s['end'],
                    'store_id' => $this->map($s['storeId']), 'created_at' => $now, 'updated_at' => $now,
                ]);
                foreach ($s['visits'] ?? [] as $v) {
                    DB::table('shift_store_visits')->insert(['shift_id' => $shiftId, 'store_id' => $this->ids[$v['storeId']], 'from_time' => $v['from']]);
                }
            }

            $rows = [];
            foreach ($d['reports'] as $r) {
                $uid = $this->ids[$r['userId']];
                $date = $move($r['date']);
                $storeId = $this->map($d['shifts'][$r['userId'].'|'.$r['date']]['storeId'] ?? null);
                $lines = $r['transactions'] ?? array_map(fn ($i) => $i + ['time' => null, 'storeId' => null], $r['items']);
                foreach ($lines as $t) {
                    $rows[] = [
                        'client_id' => (string) Str::uuid(), 'user_id' => $uid, 'date' => $date, 'time' => ($t['time'] ?? '') ?: null,
                        'store_id' => $this->map($t['storeId'] ?? null) ?? $storeId, 'product_id' => $this->ids[$t['productId']],
                        'qty' => $t['qty'], 'price' => $t['price'], 'subtotal' => $t['qty'] * $t['price'], 'created_by' => $uid,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                if ($r['noSales'] || $r['unlocked']) {
                    DB::table('day_reports')->insert(['user_id' => $uid, 'date' => $date, 'no_sales' => $r['noSales'], 'unlocked' => $r['unlocked'], 'created_at' => $now, 'updated_at' => $now]);
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('sales')->insert($chunk);
            }
        });
    }

    /** Whole weeks between the demo's today and the app's today (0 when the app's today is not later). */
    private function offsetDays(string $fixtureToday, string $today): int
    {
        $days = (int) CarbonImmutable::parse($fixtureToday)->diffInDays(CarbonImmutable::parse($today), false);

        return $days > 0 ? intdiv($days, 7) * 7 : 0;
    }

    /** Targets for every month the (moved) data covers, plus the current month, copied from the user's latest demo target. */
    private function targets(array $targets, callable $move, $now): void
    {
        $latest = [];
        foreach ($targets as $key => $t) {
            [$uid, $month] = explode('|', $key);
            if (! isset($latest[$uid]) || $month > $latest[$uid][0]) {
                $latest[$uid] = [$month, $t];
            }
        }
        foreach ($targets as $key => $t) {
            [$uid, $month] = explode('|', $key);
            $months = [substr($move($month.'-01'), 0, 7), substr($move(CarbonImmutable::parse($month.'-01')->endOfMonth()->toDateString()), 0, 7)];
            foreach (array_unique($months) as $m) {
                DB::table('targets')->insertOrIgnore([
                    'user_id' => $this->ids[$uid], 'month' => $m, 'monthly' => $t['monthly'], 'weekly' => $t['weekly'], 'daily' => $t['daily'],
                    'set_by' => $this->map($t['setBy']), 'set_at' => $t['setAt'], 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        $current = substr(Clock::today(), 0, 7);
        foreach ($latest as $uid => [, $t]) {
            DB::table('targets')->insertOrIgnore([
                'user_id' => $this->ids[$uid], 'month' => $current, 'monthly' => $t['monthly'], 'weekly' => $t['weekly'], 'daily' => $t['daily'],
                'set_by' => $this->map($t['setBy']), 'set_at' => $t['setAt'], 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function map(?string $demoId): ?int
    {
        return $demoId === null || $demoId === '' ? null : ($this->ids[$demoId] ?? null);
    }
}
