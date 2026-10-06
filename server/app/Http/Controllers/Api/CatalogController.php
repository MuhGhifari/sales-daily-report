<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Product;
use App\Models\Store;
use App\Services\Access;
use App\Services\Serializer;
use App\Support\Images;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Products and stores: shared lists. Admin, Supervisor and Team Leader add;
 * Admin and Supervisor edit any item, a Team Leader only items they added.
 */
class CatalogController extends Controller
{
    public function saveProduct(Request $request, ?Product $product = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'price' => 'required|integer|min:1|max:1000000000',
            'active' => 'boolean',
            'image' => 'nullable|string|max:3000000',
        ], ['sku.unique' => 'SKU sudah dipakai produk lain.', 'price.min' => 'Isi harga yang benar.']);
        if ($error = $this->denied($product, 'produk')) {
            return $error;
        }
        $product ??= new Product(['created_by' => $this->me()->id]);
        $old = $product->photo_path;
        $product->fill([
            'name' => trim($data['name']), 'sku' => ($data['sku'] ?? '') ?: null, 'price' => $data['price'],
            'active' => $data['active'] ?? true, 'updated_by' => $this->me()->id,
        ]);
        if (Images::isDataUrl($data['image'] ?? null)) {
            $product->photo_path = Images::store($data['image'], 'products');
        }
        $action = $product->exists ? 'ubah' : 'tambah';
        $product->save();
        if ($old !== $product->photo_path) {
            Images::delete($old);
        }
        ActivityLog::record($this->me()->id, $action, 'produk', $product->id, $product->name);

        return $this->ok(['product' => Serializer::product($product)]);
    }

    public function saveStore(Request $request, ?Store $store = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'chain' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'active' => 'boolean',
        ], ['name.required' => 'Isi nama dan kota toko.', 'city.required' => 'Isi nama dan kota toko.']);
        if ($error = $this->denied($store, 'toko')) {
            return $error;
        }
        $me = $this->me();
        $store ??= new Store(['created_by' => $me->id, 'area_id' => $me->areaId() ?? Area::orderBy('id')->value('id')]);
        $store->fill([
            'name' => trim($data['name']), 'chain' => ($data['chain'] ?? '') ?: null, 'city' => trim($data['city']),
            'address' => ($data['address'] ?? '') ?: null, 'active' => $data['active'] ?? true, 'updated_by' => $me->id,
        ]);
        $action = $store->exists ? 'ubah' : 'tambah';
        $store->save();
        ActivityLog::record($me->id, $action, 'toko', $store->id, $store->name);

        return $this->ok(['store' => Serializer::store($store)]);
    }

    private function denied($item, string $kind)
    {
        $me = $this->me();
        if ($item && ! Access::canEditCatalog($me, $item)) {
            return $this->fail('Kamu hanya bisa mengubah '.$kind.' yang kamu tambahkan.', 403);
        }
        if (! $item && ! Access::canAddCatalog($me)) {
            return $this->fail('Kamu tidak punya akses untuk menambah '.$kind.'.', 403);
        }

        return null;
    }
}
