<?php

namespace Tests;

use App\Models\Area;
use App\Models\Product;
use App\Models\Store;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected array $f = [];

    /** A small JPEG as the browser sends it (data URL). */
    protected function jpeg(): string
    {
        $img = imagecreatetruecolor(8, 8);
        ob_start();
        imagejpeg($img);

        return 'data:image/jpeg;base64,'.base64_encode(ob_get_clean());
    }

    /**
     * Small fixture: one area with two teams.
     * admin, supervisor (area), leader1 + spg1a/spg1b (team 1), leader2 + spg2 (team 2), a store and a product.
     */
    protected function fixture(): array
    {
        $area = Area::create(['name' => 'Jabodetabek']);
        $t1 = Team::create(['name' => 'Jakarta Selatan', 'area_id' => $area->id]);
        $t2 = Team::create(['name' => 'Bekasi', 'area_id' => $area->id]);
        foreach ([$t1, $t2] as $t) {
            TeamSetting::create(['team_id' => $t->id] + TeamSetting::defaults());
        }
        $mk = fn ($phone, $role, $attrs = []) => User::create(['phone' => $phone, 'name' => ucfirst($role).' '.$phone, 'role' => $role, 'password' => User::DEFAULT_PASSWORD[$role], 'active' => true] + $attrs);
        $this->f = [
            'area' => $area, 't1' => $t1, 't2' => $t2,
            'admin' => $mk('081100000001', 'admin'),
            'supervisor' => $mk('081100000002', 'supervisor', ['area_id' => $area->id]),
            'leader1' => $mk('081200000001', 'leader', ['team_id' => $t1->id]),
            'leader2' => $mk('081200000002', 'leader', ['team_id' => $t2->id]),
            'spg1a' => $mk('081300000001', 'spg', ['team_id' => $t1->id]),
            'spg1b' => $mk('081300000002', 'spg', ['team_id' => $t1->id]),
            'spg2' => $mk('081300000003', 'spg', ['team_id' => $t2->id]),
        ];
        $t1->update(['leader_id' => $this->f['leader1']->id]);
        $t2->update(['leader_id' => $this->f['leader2']->id]);
        $this->f['store'] = Store::create(['name' => 'Toko Kemang', 'city' => 'Jakarta Selatan', 'area_id' => $area->id, 'created_by' => $this->f['admin']->id]);
        $this->f['product'] = Product::create(['name' => 'NIVEA Soft 100ml', 'price' => 45000, 'created_by' => $this->f['admin']->id]);

        return $this->f;
    }
}
