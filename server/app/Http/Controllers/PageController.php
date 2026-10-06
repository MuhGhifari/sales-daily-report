<?php

namespace App\Http\Controllers;

use App\Services\StateBuilder;
use Illuminate\Http\Request;

/**
 * The pages (Blade views in resources/views). Each page gets the logged-in user's data
 * embedded (same as /api/bootstrap), so it renders without waiting for another request.
 */
class PageController extends Controller
{
    /** Page => roles that may open it (the same rules the page scripts check). */
    public const PAGES = [
        'spg/beranda' => ['spg'],
        'spg/laporan' => ['spg', 'leader', 'supervisor'],
        'spg/peringkat' => ['spg'],
        'spg/riwayat' => ['spg'],
        'leader/dashboard' => ['leader', 'supervisor'],
        'leader/laporan' => ['leader', 'supervisor'],
        'leader/target' => ['leader', 'supervisor'],
        'leader/tim' => ['leader', 'supervisor'],
        'leader/pengaturan' => ['leader', 'supervisor'],
        'supervisor/dashboard' => ['supervisor'],
        'katalog/produk' => ['admin', 'supervisor', 'leader'],
        'katalog/toko' => ['admin', 'supervisor', 'leader'],
        'admin/pengguna' => ['admin'],
    ];

    public const HOME = ['spg' => 'spg/beranda', 'leader' => 'leader/dashboard', 'supervisor' => 'supervisor/dashboard', 'admin' => 'admin/pengguna'];

    /** Login page; signed-in users go to their home page (unless they must choose a new password first). */
    public function login(Request $request)
    {
        $me = $request->user();
        if ($me && $me->active && ! $me->must_change_password) {
            return redirect()->route('page', self::HOME[$me->role]);
        }

        return view('auth.login', ['bootstrap' => $me && $me->active ? StateBuilder::build($me) : null]);
    }

    public function show(Request $request, string $page)
    {
        $me = $request->user();
        if ($me->must_change_password) {
            return redirect()->route('login');
        }
        if (! in_array($me->role, self::PAGES[$page], true)) {
            return redirect()->route('page', self::HOME[$me->role]);
        }

        return response()
            ->view($page, ['bootstrap' => StateBuilder::build($me)])
            ->header('Cache-Control', 'no-store'); // the page carries the user's data
    }
}
