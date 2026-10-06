<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    public const UPDATED_AT = null;
    protected $fillable = ['user_id', 'action', 'kind', 'subject_id', 'name', 'ip'];

    public static function record(?int $userId, string $action, string $kind, $subjectId = null, ?string $name = null): void
    {
        static::create(['user_id' => $userId, 'action' => $action, 'kind' => $kind, 'subject_id' => $subjectId !== null ? (string) $subjectId : null, 'name' => $name, 'ip' => request()?->ip()]);
    }
}
