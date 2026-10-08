<?php

namespace Tests\Feature;

use App\Models\DayReport;
use App\Models\Sale;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture();
    }

    private function sale(array $over = []): array
    {
        return array_merge([
            'clientId' => (string) Str::uuid(), 'userId' => $this->f['spg1a']->id, 'date' => '2026-10-22',
            'productId' => $this->f['product']->id, 'qty' => 2, 'price' => 45000, 'time' => '10:15',
        ], $over);
    }

    public function test_spg_records_sales_during_a_shift_and_resending_is_ignored(): void
    {
        $this->actingAs($this->f['spg1a']);
        $this->postJson('/api/sales', $this->sale())->assertStatus(422); // no shift yet
        $this->postJson('/api/shifts/start', ['storeId' => $this->f['store']->id, 'time' => '09:00'])->assertOk();

        $sale = $this->sale();
        $this->postJson('/api/sales', $sale)->assertOk()->assertJsonPath('storeId', (string) $this->f['store']->id);
        $this->postJson('/api/sales', $sale)->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame(1, Sale::count());
        $this->assertSame(90000, Sale::first()->subtotal);

        $this->getJson('/api/bootstrap')->assertOk()
            ->assertJsonPath('reports.'.$this->f['spg1a']->id.'|2026-10-22.total', 90000)
            ->assertJsonPath('reports.'.$this->f['spg1a']->id.'|2026-10-22.transactions.0.id', $sale['clientId']);
    }

    public function test_ending_a_shift_without_sales_reports_no_sales(): void
    {
        $this->actingAs($this->f['spg1a']);
        $this->postJson('/api/shifts/start', ['storeId' => $this->f['store']->id])->assertOk();
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->postJson('/api/shifts/end', ['time' => '21:00'])->assertStatus(422); // photo of the notes is required
        $this->postJson('/api/shifts/end', ['time' => '21:00', 'photos' => [$this->jpeg()]])->assertOk();
        $this->assertTrue(DayReport::where('user_id', $this->f['spg1a']->id)->value('no_sales'));
        $this->getJson('/api/bootstrap')->assertJsonPath('reports.'.$this->f['spg1a']->id.'|2026-10-22.noSales', true);
    }

    public function test_spg_cannot_edit_days_outside_the_edit_window_until_unlocked(): void
    {
        $old = $this->sale(['date' => '2026-10-15']);
        $this->actingAs($this->f['spg1a'])->postJson('/api/sales', $old)->assertStatus(403);
        $this->actingAs($this->f['leader1'])->postJson('/api/day-reports/unlock', ['userId' => $this->f['spg1a']->id, 'date' => '2026-10-15', 'unlocked' => true])->assertOk();
        $this->actingAs($this->f['spg1a'])->postJson('/api/sales', $old)->assertOk();
    }

    public function test_spg_cannot_record_for_someone_else_or_the_future(): void
    {
        $this->actingAs($this->f['spg1a']);
        $this->postJson('/api/sales', $this->sale(['userId' => $this->f['spg1b']->id, 'date' => '2026-10-21']))->assertStatus(403);
        $this->postJson('/api/sales', $this->sale(['date' => '2026-10-23']))->assertStatus(403);
    }

    public function test_leader_edits_own_team_only_and_supervisor_their_area(): void
    {
        $this->actingAs($this->f['leader1'])->postJson('/api/sales', $this->sale(['date' => '2026-10-10']))->assertOk();
        $this->actingAs($this->f['leader1'])->postJson('/api/sales', $this->sale(['userId' => $this->f['spg2']->id, 'date' => '2026-10-10']))->assertStatus(403);
        $this->actingAs($this->f['supervisor'])->postJson('/api/sales', $this->sale(['userId' => $this->f['spg2']->id, 'date' => '2026-10-10']))->assertOk();
        $this->actingAs($this->f['admin'])->postJson('/api/sales', $this->sale(['date' => '2026-10-10']))->assertStatus(403);
    }

    public function test_removing_the_last_sale_of_an_ended_shift_marks_no_sales(): void
    {
        $this->actingAs($this->f['spg1a']);
        $this->postJson('/api/shifts/start', ['storeId' => $this->f['store']->id])->assertOk();
        $sale = $this->sale();
        $this->postJson('/api/sales', $sale)->assertOk();
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->postJson('/api/shifts/end', ['photos' => [$this->jpeg()]])->assertOk();
        $this->deleteJson('/api/sales/'.$sale['clientId'])->assertOk();
        $this->assertSoftDeleted('sales', ['client_id' => $sale['clientId']]);
        $this->assertTrue(DayReport::where('user_id', $this->f['spg1a']->id)->value('no_sales'));
        $this->deleteJson('/api/sales/'.$sale['clientId'])->assertOk()->assertJsonPath('missing', true);
    }

    public function test_switching_store_moves_the_next_sales(): void
    {
        $other = \App\Models\Store::create(['name' => 'Toko Blok M', 'city' => 'Jakarta Selatan']);
        $this->actingAs($this->f['spg1a']);
        $this->postJson('/api/shifts/start', ['storeId' => $this->f['store']->id, 'time' => '09:00']);
        $this->postJson('/api/shifts/switch', ['storeId' => $other->id, 'time' => '12:00'])->assertOk();
        $this->postJson('/api/sales', $this->sale(['time' => '13:00']))->assertJsonPath('storeId', (string) $other->id);
        $this->assertCount(2, Shift::first()->visits);
    }

    public function test_notes_photos_are_saved_and_shown_only_to_the_spg_and_their_leaders(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs($this->f['spg1a']);
        $this->postJson('/api/shifts/start', ['storeId' => $this->f['store']->id, 'time' => '09:00'])->assertOk();
        $this->postJson('/api/shifts/end', ['photos' => array_fill(0, 5, $this->jpeg())])->assertStatus(422); // max 4
        $this->postJson('/api/shifts/end', ['photos' => ['data:text/plain;base64,eA==']])->assertStatus(422);
        $res = $this->postJson('/api/shifts/end', ['time' => '21:00', 'photos' => [$this->jpeg(), $this->jpeg()]])->assertOk();
        $paths = $res->json('shift.photos');
        $this->assertCount(2, $paths);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists(substr($paths[0], strlen('storage/')));
        $this->postJson('/api/shifts/end', ['photos' => [$this->jpeg()]])->assertOk(); // resend: no extra photo
        $this->assertSame(2, \App\Models\ShiftPhoto::count());

        $key = 'shifts.'.$this->f['spg1a']->id.'|2026-10-22.photos';
        $this->getJson('/api/bootstrap')->assertJsonCount(2, $key);
        $this->actingAs($this->f['leader1'])->getJson('/api/bootstrap')->assertJsonCount(2, $key);
        $this->actingAs($this->f['spg1b'])->getJson('/api/bootstrap')->assertJsonCount(0, $key); // teammate
    }
}
