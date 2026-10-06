<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    protected $fillable = ['name', 'area_id', 'leader_id'];

    public function settings(): HasOne
    {
        return $this->hasOne(TeamSetting::class);
    }
}
