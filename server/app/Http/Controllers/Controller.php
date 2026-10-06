<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function me(): User
    {
        return request()->user();
    }

    /** Error the pages show as a toast. */
    protected function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    protected function ok(array $data = []): JsonResponse
    {
        return response()->json(['ok' => true] + $data);
    }
}
