<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A photo of the SPG's handwritten sales notes, taken when ending a shift. */
class ShiftPhoto extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['shift_id', 'path'];
}
