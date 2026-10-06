<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\StateBuilder;
use App\Support\Clock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /** Sets the XSRF-TOKEN cookie (every web response does); the pages call this before their first write. */
    public function csrf()
    {
        return response()->noContent();
    }

    public function login(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string|max:30', 'password' => 'required|string|max:100', 'remember' => 'boolean']);
        $phone = Clock::phone($data['phone']);
        $key = 'login|'.$phone.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->fail('Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.', 429);
        }

        $guard = Auth::guard('web');
        $guard->setRememberDuration(60 * 24 * 30); // "Ingat saya": 30 days
        if (! $guard->attempt(['phone' => $phone, 'password' => $data['password'], 'active' => true], (bool) ($data['remember'] ?? false))) {
            RateLimiter::hit($key, 60);

            return $this->fail('Nomor HP atau password salah.');
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var User $me */
        $me = $guard->user();
        $me->forceFill(['last_login_at' => now()])->saveQuietly();
        ActivityLog::record($me->id, 'login', 'sesi', $me->id, $me->name);

        return response()->json(StateBuilder::build($me));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->ok();
    }

    /** Own password. After a reset (must_change_password) the current password is not asked again. */
    public function password(Request $request)
    {
        $me = $this->me();
        $data = $request->validate([
            'current' => $me->must_change_password ? 'nullable' : 'required|string',
            'password' => 'required|string|min:6|max:100|confirmed',
        ], [
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'current.required' => 'Isi password lama.',
        ]);
        if (! $me->must_change_password && ! Hash::check($data['current'], $me->password)) {
            return $this->fail('Password lama salah.');
        }
        if (Hash::check($data['password'], $me->password)) {
            return $this->fail('Gunakan password yang berbeda dari sebelumnya.');
        }
        $me->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        $me->endSessions($request->session()->getId());
        ActivityLog::record($me->id, 'ganti password', 'pengguna', $me->id, $me->name);

        return $this->ok();
    }
}
