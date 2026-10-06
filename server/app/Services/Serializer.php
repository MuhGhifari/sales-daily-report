<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use App\Models\Team;
use App\Models\User;

/** Converts models to the JSON shape the pages use (same as the demo's data.js; ids as strings). */
class Serializer
{
    public static function id($v): ?string
    {
        return $v === null ? null : (string) $v;
    }

    public static function user(User $u, bool $withPhone): array
    {
        return [
            'id' => (string) $u->id,
            'name' => $u->name,
            'role' => $u->role,
            'teamId' => static::id($u->team_id),
            'areaId' => static::id($u->area_id),
            'homeStoreId' => static::id($u->home_store_id),
            'photo' => $u->photo_path ?? '',
            'phone' => $withPhone ? $u->phone : '',
            'active' => (bool) $u->active,
            'username' => '',
        ];
    }

    public static function team(Team $t): array
    {
        return ['id' => (string) $t->id, 'name' => $t->name, 'areaId' => (string) $t->area_id, 'leaderId' => static::id($t->leader_id)];
    }

    public static function product(Product $p): array
    {
        return [
            'id' => (string) $p->id, 'name' => $p->name, 'sku' => $p->sku ?? '', 'price' => (int) $p->price,
            'image' => $p->photo_path ?? 'assets/products/placeholder.svg', 'active' => (bool) $p->active,
            'createdBy' => static::id($p->created_by), 'updatedBy' => static::id($p->updated_by), 'updatedAt' => optional($p->updated_at)->toDateString(),
        ];
    }

    public static function store(Store $s): array
    {
        return [
            'id' => (string) $s->id, 'name' => $s->name, 'chain' => $s->chain ?? '', 'city' => $s->city, 'address' => $s->address ?? '',
            'areaId' => static::id($s->area_id), 'active' => (bool) $s->active,
            'createdBy' => static::id($s->created_by), 'updatedBy' => static::id($s->updated_by), 'updatedAt' => optional($s->updated_at)->toDateString(),
        ];
    }
}
