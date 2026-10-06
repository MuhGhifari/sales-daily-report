<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['name', 'chain', 'city', 'address', 'area_id', 'active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
