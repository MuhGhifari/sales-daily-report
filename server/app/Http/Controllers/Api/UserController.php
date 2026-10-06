<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use App\Services\Access;
use App\Services\Serializer;
use App\Services\StateBuilder;
use App\Support\Clock;
use App\Support\Images;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Accounts: Admin adds Supervisors & Team Leaders; Leader/Supervisor add SPGs (own team / area). */
class UserController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'role' => 'required|in:spg,leader,supervisor',
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'teamId' => 'nullable|integer',
            'newTeam' => 'nullable|string|max:100',
            'areaId' => 'nullable|integer',
        ], ['name.required' => 'Nama dan nomor HP wajib diisi.', 'phone.required' => 'Nama dan nomor HP wajib diisi.']);
        $me = $this->me();
        $phone = Clock::phone($data['phone']);
        $name = trim($data['name']);
        if (! preg_match('/^08\d{8,11}$/', $phone)) {
            return $this->fail('Nomor HP tidak valid (contoh: 0812 3456 7890).');
        }
        if (User::where('phone', $phone)->exists()) {
            return $this->fail('Nomor HP sudah terdaftar.');
        }

        $u = new User(['name' => $name, 'phone' => $phone, 'role' => $data['role'], 'password' => User::DEFAULT_PASSWORD[$data['role']], 'active' => true]);
        $u->must_change_password = true;
        $newTeam = null;
        if ($data['role'] === 'spg') {
            $team = Team::find($data['teamId'] ?? 0);
            if (! $team) {
                return $this->fail('Pilih tim.');
            }
            $u->team_id = $team->id;
        } elseif ($data['role'] === 'leader') {
            if (! empty($data['newTeam'])) {
                $area = Area::find($data['areaId'] ?? 0) ?? Area::orderBy('id')->first();
                if (! $area) {
                    return $this->fail('Buat area dulu.');
                }
                $newTeam = new Team(['name' => trim($data['newTeam']), 'area_id' => $area->id]);
            } else {
                $team = Team::find($data['teamId'] ?? 0);
                if (! $team) {
                    return $this->fail('Pilih tim atau buat tim baru.');
                }
                $u->team_id = $team->id;
            }
        } else {
            $area = Area::find($data['areaId'] ?? 0) ?? Area::orderBy('id')->first();
            if (! $area) {
                return $this->fail('Buat area dulu.');
            }
            $u->area_id = $area->id;
        }
        // Permission check on the account as it would be created (a new team counts as manageable for Admin)
        if (! $newTeam && ! Access::canManageUser($me, $u)) {
            return $this->fail('Kamu tidak punya akses untuk menambah pengguna ini.', 403);
        }
        if ($newTeam && $me->role !== 'admin') {
            return $this->fail('Kamu tidak punya akses untuk menambah pengguna ini.', 403);
        }

        $team = DB::transaction(function () use ($u, $newTeam) {
            if ($newTeam) {
                $newTeam->save();
                TeamSetting::create(['team_id' => $newTeam->id] + TeamSetting::defaults());
                $u->team_id = $newTeam->id;
            }
            $u->save();
            $team = $u->team_id ? Team::find($u->team_id) : null;
            // A new leader leads the team when it has no (active) leader
            if ($u->role === 'leader' && $team && (! $team->leader_id || ! User::where('id', $team->leader_id)->where('active', true)->exists())) {
                $team->update(['leader_id' => $u->id]);
            }

            return $team;
        });
        ActivityLog::record($me->id, 'tambah', 'pengguna', $u->id, $u->name);

        return $this->ok([
            'user' => Serializer::user($u->fresh(), true),
            'team' => $team ? Serializer::team($team->fresh()) : null,
            'settings' => $newTeam ? StateBuilder::settings([$newTeam->id])[(string) $newTeam->id] : null,
        ]);
    }

    public function active(Request $request, User $user)
    {
        $data = $request->validate(['active' => 'required|boolean']);
        $me = $this->me();
        if (! Access::canManageUser($me, $user)) {
            return $this->fail('Kamu tidak punya akses.', 403);
        }
        $user->update(['active' => $data['active']]);
        if (! $data['active']) {
            $user->endSessions();
        }
        ActivityLog::record($me->id, $data['active'] ? 'aktifkan' : 'nonaktifkan', 'pengguna', $user->id, $user->name);

        return $this->ok();
    }

    /** Back to the role's default password; the user must choose a new one at the next login. */
    public function resetPassword(User $user)
    {
        $me = $this->me();
        if (! Access::canManageUser($me, $user)) {
            return $this->fail('Kamu tidak punya akses.', 403);
        }
        $password = User::DEFAULT_PASSWORD[$user->role];
        $user->forceFill(['password' => $password, 'must_change_password' => true])->save();
        $user->endSessions();
        ActivityLog::record($me->id, 'reset password', 'pengguna', $user->id, $user->name);

        return $this->ok(['password' => $password]);
    }

    /** Profile photo: own photo, or of someone the viewer manages. */
    public function photo(Request $request, User $user)
    {
        $data = $request->validate(['photo' => 'required|string|max:3000000']);
        $me = $this->me();
        if ($me->id !== $user->id && ! Access::canManageUser($me, $user)) {
            return $this->fail('Kamu tidak punya akses.', 403);
        }
        $old = $user->photo_path;
        $user->update(['photo_path' => Images::store($data['photo'], 'photos')]);
        Images::delete($old);

        return $this->ok(['photo' => $user->photo_path]);
    }
}
