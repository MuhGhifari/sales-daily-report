<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\PageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Pages (Blade views in resources/views)
Route::get('/', [PageController::class, 'login'])->name('login');
Route::get('{page}', [PageController::class, 'show'])
    ->whereIn('page', array_keys(PageController::PAGES))
    ->middleware(['auth', 'active'])
    ->name('page');
// Old addresses of the static demo (e.g. spg/beranda.html, index.html)
Route::get('{page}.html', function (Request $request, string $page) {
    $to = isset(PageController::PAGES[$page]) ? route('page', $page) : url('/');

    return redirect($to.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
})->where('page', '[a-z]+(/[a-z]+)?');

// JSON API on the web middleware: session cookie + CSRF (X-XSRF-TOKEN header)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('csrf', [AuthController::class, 'csrf'])->name('csrf');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('password', [AuthController::class, 'password'])->name('password');
        Route::get('bootstrap', [StateController::class, 'bootstrap'])->name('bootstrap');

        Route::post('shifts/start', [ShiftController::class, 'start']);
        Route::post('shifts/switch', [ShiftController::class, 'switchStore']);
        Route::post('shifts/end', [ShiftController::class, 'end']);

        Route::post('sales', [SaleController::class, 'store']);
        Route::delete('sales/{clientId}', [SaleController::class, 'destroy']);
        Route::post('day-reports/unlock', [SaleController::class, 'unlock']);

        Route::post('targets', [TeamController::class, 'targets']);
        Route::put('teams/{team}/settings', [TeamController::class, 'settings']);

        Route::post('users', [UserController::class, 'store']);
        Route::post('users/{user}/active', [UserController::class, 'active']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('users/{user}/photo', [UserController::class, 'photo']);

        Route::post('products', [CatalogController::class, 'saveProduct']);
        Route::put('products/{product}', [CatalogController::class, 'saveProduct']);
        Route::post('stores', [CatalogController::class, 'saveStore']);
        Route::put('stores/{store}', [CatalogController::class, 'saveStore']);
    });
});
