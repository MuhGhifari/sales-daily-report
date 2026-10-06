<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture();
    }

    public function test_admin_adds_supervisors_and_leaders_but_not_spgs(): void
    {
        $this->actingAs($this->f['admin']);
        $this->postJson('/api/users', ['role' => 'spg', 'name' => 'X', 'phone' => '081377777777', 'teamId' => $this->f['t1']->id])->assertStatus(403);
        $this->postJson('/api/users', ['role' => 'supervisor', 'name' => 'Sup', 'phone' => '081177777777', 'areaId' => $this->f['area']->id])->assertOk();
        $res = $this->postJson('/api/users', ['role' => 'leader', 'name' => 'Lead', 'phone' => '0812 7777 7777', 'newTeam' => 'Bogor'])->assertOk();
        $this->assertSame('Bogor', $res->json('team.name'));
        $this->assertSame($res->json('user.id'), $res->json('team.leaderId'));
        $this->assertNotNull($res->json('settings.workingDays'));
        $this->assertTrue(User::where('phone', '081277777777')->value('must_change_password'));
    }

    public function test_leader_adds_spgs_to_own_team_only(): void
    {
        $this->actingAs($this->f['leader1']);
        $this->postJson('/api/users', ['role' => 'spg', 'name' => 'Baru', 'phone' => '081377777777', 'teamId' => $this->f['t1']->id])->assertOk();
        $this->postJson('/api/users', ['role' => 'spg', 'name' => 'Lain', 'phone' => '081377777778', 'teamId' => $this->f['t2']->id])->assertStatus(403);
        $this->postJson('/api/users', ['role' => 'leader', 'name' => 'L', 'phone' => '081277777777', 'teamId' => $this->f['t1']->id])->assertStatus(403);
        $this->postJson('/api/users', ['role' => 'spg', 'name' => 'Dobel', 'phone' => '081377777777', 'teamId' => $this->f['t1']->id])->assertStatus(422)->assertJsonPath('message', 'Nomor HP sudah terdaftar.');
    }

    public function test_supervisor_manages_spgs_in_area_but_not_leaders(): void
    {
        $this->actingAs($this->f['supervisor']);
        $this->postJson('/api/users', ['role' => 'spg', 'name' => 'Baru', 'phone' => '081377777777', 'teamId' => $this->f['t2']->id])->assertOk();
        $this->postJson('/api/users/'.$this->f['spg1a']->id.'/active', ['active' => false])->assertOk();
        $this->postJson('/api/users/'.$this->f['leader1']->id.'/active', ['active' => false])->assertStatus(403);
    }

    public function test_targets_only_for_own_team(): void
    {
        $this->actingAs($this->f['leader1']);
        $this->postJson('/api/targets', ['rows' => [['userId' => $this->f['spg1a']->id, 'mk' => '2026-11', 'monthly' => 40000000]]])->assertOk();
        $this->assertSame(40000000, Target::where('user_id', $this->f['spg1a']->id)->value('monthly'));
        $this->postJson('/api/targets', ['rows' => [['userId' => $this->f['spg2']->id, 'mk' => '2026-11', 'monthly' => 1]]])->assertStatus(403);
    }

    public function test_team_settings(): void
    {
        $body = ['workingDays' => [1, 2, 3, 4, 5], 'editDays' => 3, 'reminder' => '19:30', 'holidays' => [['date' => '2026-12-25', 'name' => 'Natal', 'working' => false]]];
        $this->actingAs($this->f['leader2'])->putJson('/api/teams/'.$this->f['t1']->id.'/settings', $body)->assertStatus(403);
        $this->actingAs($this->f['leader1'])->putJson('/api/teams/'.$this->f['t1']->id.'/settings', $body)->assertOk();
        $this->actingAs($this->f['leader1'])->getJson('/api/bootstrap')
            ->assertJsonPath('settings.'.$this->f['t1']->id.'.editDays', 3)
            ->assertJsonPath('settings.'.$this->f['t1']->id.'.holidays.0.name', 'Natal');
    }

    public function test_catalog_leaders_edit_only_what_they_added(): void
    {
        $this->actingAs($this->f['leader1']);
        $this->putJson('/api/products/'.$this->f['product']->id, ['name' => 'X', 'price' => 1000])->assertStatus(403);
        $id = $this->postJson('/api/products', ['name' => 'NIVEA Baru', 'price' => 25000])->assertOk()->json('product.id');
        $this->putJson('/api/products/'.$id, ['name' => 'NIVEA Baru 2', 'price' => 26000])->assertOk();
        $this->actingAs($this->f['supervisor'])->putJson('/api/products/'.$this->f['product']->id, ['name' => 'NIVEA Soft', 'price' => 46000])->assertOk();
        $this->assertSame(46000, Product::find($this->f['product']->id)->price);

        $this->actingAs($this->f['leader1'])->putJson('/api/stores/'.$this->f['store']->id, ['name' => 'X', 'city' => 'Y'])->assertStatus(403);
        $this->postJson('/api/stores', ['name' => 'Toko Baru', 'city' => 'Depok'])->assertOk();
        $this->actingAs($this->f['spg1a'])->postJson('/api/stores', ['name' => 'Toko SPG', 'city' => 'Depok'])->assertStatus(403);
        $this->assertSame(2, Store::count());
    }

    public function test_photo_upload_validates_and_stores_the_image(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $img = imagecreatetruecolor(8, 8);
        ob_start();
        imagejpeg($img);
        $url = 'data:image/jpeg;base64,'.base64_encode(ob_get_clean());
        $this->actingAs($this->f['spg1a']);
        $path = $this->postJson('/api/users/'.$this->f['spg1a']->id.'/photo', ['photo' => $url])->assertOk()->json('photo');
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists(substr($path, strlen('storage/')));
        $this->postJson('/api/users/'.$this->f['spg1a']->id.'/photo', ['photo' => 'data:image/jpeg;base64,bm90IGFuIGltYWdl'])->assertStatus(422);
        $this->postJson('/api/users/'.$this->f['spg1b']->id.'/photo', ['photo' => $url])->assertStatus(403);
    }

    public function test_what_each_role_sees(): void
    {
        $spgData = $this->actingAs($this->f['spg1a'])->getJson('/api/bootstrap')->json();
        $phones = collect($spgData['users'])->pluck('phone', 'id');
        $this->assertSame('081300000001', $phones[(string) $this->f['spg1a']->id]);
        $this->assertSame('', $phones[(string) $this->f['spg1b']->id]); // teammates: no phone numbers
        $this->assertArrayNotHasKey((string) $this->f['spg2']->id, $phones->all()); // other team not sent
        $this->assertSame([(string) $this->f['t1']->id], collect($spgData['teams'])->pluck('id')->all());

        $sup = $this->actingAs($this->f['supervisor'])->getJson('/api/bootstrap')->json();
        $this->assertCount(2, $sup['teams']);
        $this->assertSame([], $sup['reports']);

        $admin = $this->actingAs($this->f['admin'])->getJson('/api/bootstrap')->json();
        $this->assertSame([], $admin['targets']);
    }
}
