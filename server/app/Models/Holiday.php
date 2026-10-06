<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public $timestamps = false;
    protected $fillable = ['team_id', 'date', 'name', 'is_working_day'];

    protected function casts(): array
    {
        return ['is_working_day' => 'boolean'];
    }
}
