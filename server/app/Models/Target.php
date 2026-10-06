<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $fillable = ['user_id', 'month', 'monthly', 'weekly', 'daily', 'set_by', 'set_at'];

    protected function casts(): array
    {
        return ['monthly' => 'integer', 'weekly' => 'integer', 'daily' => 'integer'];
    }
}
