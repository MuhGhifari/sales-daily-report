<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    public const DEFAULT_PASSWORD = ['spg' => 'spg123', 'leader' => 'leader123', 'supervisor' => 'super123', 'admin' => 'admin123'];

    protected $fillable = ['phone', 'name', 'role', 'area_id', 'team_id', 'home_store_id', 'photo_path', 'password', 'must_change_password', 'active', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function isRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Area the user works in (supervisor: own area, others: their team's area). */
    public function areaId(): ?int
    {
        return $this->area_id ?? $this->team?->area_id;
    }

    /** Signs the user out everywhere (except the given session): deletes their sessions and "remember me" token. */
    public function endSessions(?string $exceptSessionId = null): void
    {
        DB::table('sessions')->where('user_id', $this->id)->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))->delete();
        $this->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }
}
