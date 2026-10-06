<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayReport extends Model
{
    protected $fillable = ['user_id', 'date', 'no_sales', 'unlocked', 'unlocked_by'];

    protected function casts(): array
    {
        return ['no_sales' => 'boolean', 'unlocked' => 'boolean'];
    }
}
