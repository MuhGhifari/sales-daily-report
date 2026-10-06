<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = ['client_id', 'user_id', 'date', 'time', 'store_id', 'product_id', 'qty', 'price', 'subtotal', 'created_by'];

    protected function casts(): array
    {
        return ['qty' => 'integer', 'price' => 'integer', 'subtotal' => 'integer'];
    }
}
