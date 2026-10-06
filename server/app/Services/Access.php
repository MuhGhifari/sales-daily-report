<?php

namespace App\Services;

use App\Models\DayReport;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use App\Support\Clock;
use Carbon\CarbonImmutable;

/**
 * All permission rules in one place (mirrors the demo's data.js):
 * - Admin manages Supervisors and Team Leaders, all products and stores.
 * - Supervisor: SPGs, targets, settings and reports of every team in their area; edits any product/store.
 * - Team Leader: their own team's SPGs, targets, settings and reports; edits only products/stores they added.
 * - SPG: their own shifts and sales, today (with a shift) or inside the team's edit window.
 */
class Access
{
    /** Team ids whose data the user may see / manage. */
    public static function teamIds(User $viewer): array
    {
        return match ($viewer->role) {
            'admin' => Team::pluck('id')->all(),
            'supervisor' => Team::where('area_id', $viewer->area_id)->pluck('id')->all(),
            default => $viewer->team_id ? [$viewer->team_id] : [],
        };
    }

    /** May the viewer see this user's reports? */
    public static function canView(User $viewer, User $owner): bool
    {
        return match ($viewer->role) {
            'spg' => $viewer->id === $owner->id,
            'leader' => $owner->team_id !== null && $owner->team_id === $viewer->team_id,
            'supervisor' => $owner->team_id !== null && in_array($owner->team_id, static::teamIds($viewer), true),
            default => false,
        };
    }

    public static function canManageTeam(User $viewer, int $teamId): bool
    {
        return $viewer->isRole('leader', 'supervisor') && in_array($teamId, static::teamIds($viewer), true);
    }

    /** Admin → Supervisors & Team Leaders; Leader → own team's SPGs; Supervisor → SPGs in their area. */
    public static function canManageUser(User $viewer, User $target): bool
    {
        if ($viewer->role === 'admin') {
            return $target->isRole('leader', 'supervisor');
        }
        if ($target->role !== 'spg' || ! $target->team_id) {
            return false;
        }

        return static::canManageTeam($viewer, $target->team_id);
    }

    public static function canAddCatalog(User $viewer): bool
    {
        return $viewer->isRole('admin', 'supervisor', 'leader');
    }

    /** Products and stores: Admin and Supervisor edit any, Team Leader only what they added. */
    public static function canEditCatalog(User $viewer, $item): bool
    {
        return $viewer->isRole('admin', 'supervisor') || ($viewer->role === 'leader' && (int) $item->created_by === $viewer->id);
    }

    public static function editDays(User $owner): int
    {
        return (int) (TeamSetting::find($owner->team_id)?->edit_days ?? TeamSetting::defaults()['edit_days']);
    }

    /** Locked for the SPG: older than the team's edit window and not unlocked by a leader. */
    public static function isLocked(User $owner, string $date): bool
    {
        if (DayReport::where('user_id', $owner->id)->where('date', $date)->value('unlocked')) {
            return false;
        }
        $limit = CarbonImmutable::parse(Clock::today())->subDays(static::editDays($owner))->toDateString();

        return $date < $limit;
    }

    /** May the viewer add / remove sales of this owner on this date? */
    public static function canEditDay(User $viewer, User $owner, string $date): bool
    {
        if ($date > Clock::today() || ! static::canView($viewer, $owner)) {
            return false;
        }

        return $viewer->role === 'spg' ? ! static::isLocked($owner, $date) : true;
    }
}
