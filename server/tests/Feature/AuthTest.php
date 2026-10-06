<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture();
    }

    public function test_login_with_phone_in_any_format_returns_the_users_data(): void
    {
        $this->postJson('/api/login', ['phone' => '+62 813-0000-0001', 'password' => 'spg123'])
            ->assertOk()
            ->assertJsonPath('me', (string) $this->f['spg1a']->id)
            ->assertJsonPath('today', '2026-10-22');
        $this->assertAuthenticatedAs($this->f['spg1a']);
    }

    public function test_wrong_password_and_inactive_accounts_are_refused(): void
    {
        $this->postJson('/api/login', ['phone' => '081300000001', 'password' => 'salah'])->assertStatus(422)->assertJsonPath('message', 'Nomor HP atau password salah.');
        $this->f['spg1b']->update(['active' => false]);
        $this->postJson('/api/login', ['phone' => '081300000002', 'password' => 'spg123'])->assertStatus(422);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['phone' => '081300000001', 'password' => 'salah']);
        }
        $this->postJson('/api/login', ['phone' => '081300000001', 'password' => 'spg123'])->assertStatus(429);
    }

    public function test_api_needs_login(): void
    {
        $this->getJson('/api/bootstrap')->assertStatus(401);
        $this->postJson('/api/sales', [])->assertStatus(401);
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $this->actingAs($this->f['spg1a']);
        $this->f['spg1a']->update(['active' => false]);
        $this->getJson('/api/bootstrap')->assertStatus(401);
    }

    public function test_after_reset_the_user_must_choose_a_new_password_first(): void
    {
        $this->actingAs($this->f['leader1'])->postJson('/api/users/'.$this->f['spg1a']->id.'/reset-password')->assertOk()->assertJsonPath('password', 'spg123');
        $spg = $this->f['spg1a']->fresh();
        $this->assertTrue($spg->must_change_password);

        $this->actingAs($spg);
        $this->getJson('/api/bootstrap')->assertOk()->assertJsonPath('mustChangePassword', true);
        $this->postJson('/api/shifts/start', ['storeId' => $this->f['store']->id])->assertStatus(403);
        $this->postJson('/api/password', ['password' => 'spg123', 'password_confirmation' => 'spg123'])->assertStatus(422);
        $this->postJson('/api/password', ['password' => 'baru123', 'password_confirmation' => 'baru123'])->assertOk();
        $this->postJson('/api/shifts/start', ['storeId' => $this->f['store']->id])->assertOk();
    }

    public function test_changing_password_needs_the_current_one(): void
    {
        $this->actingAs($this->f['spg1a']);
        $this->postJson('/api/password', ['current' => 'salah', 'password' => 'baru123', 'password_confirmation' => 'baru123'])->assertStatus(422);
        $this->postJson('/api/password', ['current' => 'spg123', 'password' => 'baru123', 'password_confirmation' => 'baru123'])->assertOk();
    }
}
