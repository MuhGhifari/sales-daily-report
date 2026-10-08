<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayReport;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Store;
use App\Services\StateBuilder;
use App\Support\Clock;
use App\Support\Images;
use Illuminate\Http\Request;

/**
 * An SPG's shift today: start at a store, switch store, end.
 * Calls may arrive late from the phone's offline queue, so the phone's own time is used when given.
 */
class ShiftController extends Controller
{
    public function start(Request $request)
    {
        $data = $this->input($request);
        if (! $this->me()->isRole('spg')) {
            return $this->fail('Hanya SPG yang memulai shift.', 403);
        }
        $shift = Shift::firstOrCreate(
            ['user_id' => $this->me()->id, 'date' => Clock::today()],
            ['start_time' => $data['time'], 'store_id' => $data['storeId']],
        );
        if ($shift->wasRecentlyCreated) {
            $shift->visits()->create(['store_id' => $data['storeId'], 'from_time' => $data['time']]);
        }

        return $this->ok(['shift' => StateBuilder::shift($shift->load('visits'))]);
    }

    public function switchStore(Request $request)
    {
        $data = $this->input($request);
        $shift = $this->today();
        if (! $shift || $shift->end_time) {
            return $this->fail('Tidak ada shift yang berjalan.');
        }
        if ((int) $shift->store_id !== (int) $data['storeId']) {
            $shift->update(['store_id' => $data['storeId']]);
            $shift->visits()->create(['store_id' => $data['storeId'], 'from_time' => $data['time']]);
        }

        return $this->ok(['shift' => StateBuilder::shift($shift->load('visits'))]);
    }

    /** Ending a shift needs 1–4 photos of the SPG's handwritten sales notes. */
    public function end(Request $request)
    {
        $data = $request->validate([
            'time' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'photos' => 'array|max:4',
            'photos.*' => 'string|max:3000000',
        ], ['photos.max' => 'Maksimal 4 foto catatan.']);
        $shift = $this->today();
        if (! $shift) {
            return $this->fail('Tidak ada shift yang berjalan.');
        }
        if (! $shift->end_time) { // already ended (e.g. resent from the offline queue): nothing to do
            if (empty($data['photos'])) {
                return $this->fail('Foto catatan penjualan dulu sebelum mengakhiri shift.');
            }
            $paths = array_map(fn ($p) => Images::store($p, 'notes'), $data['photos']);
            foreach ($paths as $path) {
                $shift->photos()->create(['path' => $path]);
            }
            $shift->update(['end_time' => $data['time'] ?? Clock::time()]);
            // A shift without sales still counts as a report: "no sales"
            if (! Sale::where('user_id', $shift->user_id)->where('date', $shift->date)->exists()) {
                DayReport::updateOrCreate(['user_id' => $shift->user_id, 'date' => $shift->date], ['no_sales' => true]);
            }
        }

        return $this->ok(['shift' => StateBuilder::shift($shift->load('visits', 'photos'), true)]);
    }

    private function today(): ?Shift
    {
        return Shift::where('user_id', $this->me()->id)->where('date', Clock::today())->first();
    }

    private function input(Request $request): array
    {
        $data = $request->validate([
            'storeId' => 'required|integer',
            'time' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ]);
        abort_unless(Store::where('id', $data['storeId'])->where('active', true)->exists(), 422, 'Toko tidak ditemukan.');
        $data['time'] ??= Clock::time();

        return $data;
    }
}
