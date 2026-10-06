<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = ['user_id', 'date', 'start_time', 'end_time', 'store_id'];

    public function visits(): HasMany
    {
        return $this->hasMany(ShiftStoreVisit::class)->orderBy('id');
    }

    /** Store the shift was at at a given time (last visit that started at or before it). */
    public function storeAt(?string $time): ?int
    {
        $id = $this->store_id;
        foreach ($this->visits as $v) {
            if ($time === null || $v->from_time <= $time) {
                $id = $v->store_id;
            }
        }

        return $id;
    }
}
