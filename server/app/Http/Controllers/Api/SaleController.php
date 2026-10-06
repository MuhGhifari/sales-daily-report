<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DayReport;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Services\Access;
use App\Support\Clock;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * One sale. Idempotent: the phone generates client_id, so a resend from the offline queue
     * never records the same sale twice.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'clientId' => 'required|uuid',
            'userId' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'productId' => 'required|integer',
            'qty' => 'required|integer|min:1|max:10000',
            'price' => 'required|integer|min:0|max:1000000000',
            'storeId' => 'nullable|integer',
            'time' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ]);
        $existing = Sale::withTrashed()->where('client_id', $data['clientId'])->first();
        if ($existing) {
            return $existing->user_id === (int) $data['userId'] ? $this->ok(['duplicate' => true]) : $this->fail('ID transaksi bentrok.', 409);
        }

        $me = $this->me();
        $owner = User::where('role', 'spg')->find($data['userId']);
        if (! $owner || ! Access::canEditDay($me, $owner, $data['date'])) {
            return $this->fail('Laporan hari ini sudah dikunci atau bukan milikmu.', 403);
        }
        if (! Product::whereKey($data['productId'])->exists()) {
            return $this->fail('Produk tidak ditemukan.');
        }

        $today = $data['date'] === Clock::today();
        $shift = Shift::with('visits')->where('user_id', $owner->id)->where('date', $data['date'])->first();
        if ($me->role === 'spg' && $today && ! $shift) {
            return $this->fail('Mulai shift dulu sebelum mencatat penjualan.');
        }
        // Time: sales of today get the (phone's) time; sales added afterwards for a past day have none
        $time = $today ? ($data['time'] ?? Clock::time()) : null;
        $storeId = isset($data['storeId']) && Store::whereKey($data['storeId'])->exists()
            ? (int) $data['storeId']
            : ($shift?->storeAt($time) ?? $this->lastStoreId($owner));

        Sale::create([
            'client_id' => $data['clientId'], 'user_id' => $owner->id, 'date' => $data['date'], 'time' => $time,
            'store_id' => $storeId, 'product_id' => $data['productId'], 'qty' => $data['qty'], 'price' => $data['price'],
            'subtotal' => $data['qty'] * $data['price'], 'created_by' => $me->id,
        ]);
        DayReport::where('user_id', $owner->id)->where('date', $data['date'])->update(['no_sales' => false]);
        if ($me->id !== $owner->id) {
            ActivityLog::record($me->id, 'tambah penjualan', 'laporan', $owner->id, $owner->name.' · '.$data['date']);
        }

        return $this->ok(['storeId' => (string) $storeId, 'time' => $time ?? '']);
    }

    public function destroy(string $clientId)
    {
        $sale = Sale::where('client_id', $clientId)->first();
        if (! $sale) {
            return $this->ok(['missing' => true]); // already removed (e.g. resent from the offline queue)
        }
        $me = $this->me();
        $owner = User::find($sale->user_id);
        $date = $sale->date;
        if (! Access::canEditDay($me, $owner, $date)) {
            return $this->fail('Laporan hari ini sudah dikunci atau bukan milikmu.', 403);
        }
        $sale->delete();
        $shift = Shift::where('user_id', $owner->id)->where('date', $date)->first();
        if ($shift?->end_time && ! Sale::where('user_id', $owner->id)->where('date', $date)->exists()) {
            DayReport::updateOrCreate(['user_id' => $owner->id, 'date' => $date], ['no_sales' => true]);
        }
        if ($me->id !== $owner->id) {
            ActivityLog::record($me->id, 'hapus penjualan', 'laporan', $owner->id, $owner->name.' · '.$date);
        }

        return $this->ok();
    }

    /** Leader/Supervisor lets an SPG edit a day outside the edit window again (or locks it again). */
    public function unlock(Request $request)
    {
        $data = $request->validate(['userId' => 'required|integer', 'date' => 'required|date_format:Y-m-d', 'unlocked' => 'required|boolean']);
        $me = $this->me();
        $owner = User::find($data['userId']);
        if (! $owner || ! $me->isRole('leader', 'supervisor') || ! Access::canView($me, $owner)) {
            return $this->fail('Kamu tidak punya akses.', 403);
        }
        DayReport::updateOrCreate(['user_id' => $owner->id, 'date' => $data['date']], ['unlocked' => $data['unlocked'], 'unlocked_by' => $me->id]);
        ActivityLog::record($me->id, $data['unlocked'] ? 'buka kunci' : 'kunci', 'laporan', $owner->id, $owner->name.' · '.$data['date']);

        return $this->ok();
    }

    private function lastStoreId(User $owner): ?int
    {
        return Shift::where('user_id', $owner->id)->whereNotNull('store_id')->orderByDesc('date')->value('store_id') ?? $owner->home_store_id;
    }
}
