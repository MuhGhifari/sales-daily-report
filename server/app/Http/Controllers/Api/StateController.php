<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StateBuilder;

class StateController extends Controller
{
    /** Everything a page needs for the logged-in user (same shape as the demo's local data). */
    public function bootstrap()
    {
        return response()->json(StateBuilder::build($this->me()));
    }
}
