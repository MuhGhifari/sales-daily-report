<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Holiday;
use App\Models\Target;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use App\Services\Access;
use App\Support\Clock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Targets and team settings (working days, holidays, edit window, reminder). */
class TeamController extends Controller
{
    public function targets(Request $request)
    {
        $data = $request->validate([
            'rows' => 'required|array|max:200',
            'rows.*.userId' => 'required|integer',
            'rows.*.mk' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'rows.*.monthly' => 'nullable|integer|min:0|max:100000000000',
            'rows.*.weekly' => 'nullable|integer|min:0|max:100000000000',
            'rows.*.daily' => 'nullable|integer|min:0|max:100000000000',
        ]);
        $me = $this->me();
        $owners = User::whereIn('id', collect($data['rows'])->pluck('userId'))->get()->keyBy('id');
        foreach ($data['rows'] as $r) {
            $owner = $owners[$r['userId']] ?? null;
            if (! $owner || ! Access::canManageUser($me, $owner)) {
                return $this->fail('Kamu hanya bisa mengatur target SPG timmu.', 403);
            }
        }
        DB::transaction(function () use ($data, $me) {
            foreach ($data['rows'] as $r) {
                Target::updateOrCreate(['user_id' => $r['userId'], 'month' => $r['mk']], [
                    'monthly' => $r['monthly'] ?? 0, 'weekly' => ($r['weekly'] ?? 0) ?: null, 'daily' => ($r['daily'] ?? 0) ?: null,
                    'set_by' => $me->id, 'set_at' => Clock::today(),
                ]);
            }
        });
        ActivityLog::record($me->id, 'atur', 'target', null, count($data['rows']).' SPG · '.$data['rows'][0]['mk']);

        return $this->ok();
    }

    public function settings(Request $request, Team $team)
    {
        $me = $this->me();
        if (! Access::canManageTeam($me, $team->id)) {
            return $this->fail('Kamu tidak punya akses ke tim ini.', 403);
        }
        $data = $request->validate([
            'workingDays' => 'required|array|min:1|max:7',
            'workingDays.*' => 'integer|between:0,6',
            'editDays' => 'required|integer|between:0,14',
            'reminder' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'holidays' => 'present|array|max:100',
            'holidays.*.date' => 'required|date_format:Y-m-d|distinct',
            'holidays.*.name' => 'required|string|max:100',
            'holidays.*.working' => 'boolean',
        ], ['workingDays.min' => 'Pilih minimal 1 hari kerja.', 'editDays.between' => 'Batas ubah laporan harus 0–14 hari.']);

        DB::transaction(function () use ($team, $data) {
            TeamSetting::updateOrCreate(['team_id' => $team->id], [
                'working_days' => array_values(array_unique(array_map('intval', $data['workingDays']))),
                'edit_days' => $data['editDays'], 'reminder_time' => $data['reminder'],
            ]);
            Holiday::where('team_id', $team->id)->delete();
            foreach ($data['holidays'] as $h) {
                Holiday::create(['team_id' => $team->id, 'date' => $h['date'], 'name' => $h['name'], 'is_working_day' => (bool) ($h['working'] ?? false)]);
            }
        });
        ActivityLog::record($me->id, 'ubah', 'pengaturan', $team->id, 'Tim '.$team->name);

        return $this->ok();
    }
}
