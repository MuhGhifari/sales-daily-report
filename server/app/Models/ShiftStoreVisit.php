<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftStoreVisit extends Model
{
    public $timestamps = false;
    protected $fillable = ['shift_id', 'store_id', 'from_time'];
}
