<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamSetting extends Model
{
    protected $primaryKey = 'team_id';
    public $incrementing = false;
    protected $fillable = ['team_id', 'working_days', 'edit_days', 'reminder_time'];

    protected function casts(): array
    {
        return ['working_days' => 'array', 'edit_days' => 'integer'];
    }

    public static function defaults(): array
    {
        return ['working_days' => [1, 2, 3, 4, 5, 6], 'edit_days' => 2, 'reminder_time' => '20:00'];
    }
}
