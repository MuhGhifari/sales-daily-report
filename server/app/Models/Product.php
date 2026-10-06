<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'price', 'photo_path', 'active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'price' => 'integer'];
    }
}
