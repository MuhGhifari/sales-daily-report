<?php

namespace Tests\Feature;

use App\Http\Controllers\PageController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture();
    }

    public function test_login_page_renders_without_user_data(): void
    {
        config(['app.demo_accounts' => false]);
        $this->get('/')->assertOk()
            ->assertSee('window.APP_BOOTSTRAP = null', false)
            ->assertSee('"backend":"laravel"', false)
            ->assertDontSee('Akun demo');
        config(['app.demo_accounts' => true]);
        $this->get('/')->assertSee('Akun demo');
    }

    public function test_pages_need_login(): void
    {
        $this->get('/spg/beranda')->assertRedirect('/');
    }

    public function test_every_page_renders_for_its_roles_with_the_users_data(): void
    {
        $users = ['spg' => 'spg1a', 'leader' => 'leader1', 'supervisor' => 'supervisor', 'admin' => 'admin'];
        foreach (PageController::PAGES as $page => $roles) {
            foreach ($users as $role => $key) {
                $res = $this->actingAs($this->f[$key])->get('/'.$page);
                if (in_array($role, $roles, true)) {
                    $res->assertOk()->assertSee('window.APP_BOOTSTRAP = {', false)->assertSee('"me":"'.$this->f[$key]->id.'"', false);
                } else {
                    $res->assertRedirect(route('page', PageController::HOME[$role]));
                }
            }
        }
    }

    public function test_signed_in_users_skip_the_login_page_unless_they_must_change_password(): void
    {
        $this->actingAs($this->f['leader1'])->get('/')->assertRedirect(route('page', 'leader/dashboard'));
        $this->f['leader1']->update(['must_change_password' => true]);
        $this->actingAs($this->f['leader1'])->get('/')->assertOk()->assertSee('"mustChangePassword":true', false);
        $this->actingAs($this->f['leader1'])->get('/leader/dashboard')->assertRedirect('/');
    }

    public function test_old_demo_addresses_redirect(): void
    {
        $this->get('/spg/laporan.html?tanggal=2026-10-20')->assertRedirect(route('page', 'spg/laporan').'?tanggal=2026-10-20');
        $this->get('/index.html')->assertRedirect(url('/'));
    }
}
