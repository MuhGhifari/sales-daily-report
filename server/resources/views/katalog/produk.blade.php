@extends('layouts.app', ['title' => 'Katalog Produk', 'script' => 'katalog-produk'])

@section('content')
<main>
  <div class="page-head"><div><h1>Katalog</h1><p class="sub" id="count"></p></div><button type="button" class="btn" id="openAdd" hidden><span data-icon="plus"></span>Tambah produk</button></div>
  <nav class="tabs subtabs" aria-label="Katalog"><a href="{{ route('page', 'katalog/produk') }}" class="on">Produk</a><a href="{{ route('page', 'katalog/toko') }}" class="">Toko</a></nav>
  <section><div class="table-wrap"><table id="table"></table></div></section>
  <dialog class="modal" id="dlg" aria-labelledby="formTitle">
    <form id="form" autocomplete="off">
      <div class="modal-head"><h3 id="formTitle">Tambah produk</h3><button type="button" class="icon-btn" data-close aria-label="Tutup" data-icon="x"></button></div>
      <div class="modal-body">
        <input type="hidden" name="id">
        <div class="actions" style="margin-bottom:16px">
          <span id="imgPreview"></span>
          <button type="button" class="btn ghost small" id="pickImg">Pilih foto</button>
        </div>
        <div class="field"><label for="p-name">Nama</label><input class="input" id="p-name" name="name" required></div>
        <div class="form-grid">
          <div class="field"><label for="p-sku">SKU</label><input class="input" id="p-sku" name="sku"></div>
          <div class="field"><label for="p-price">Harga (Rp)</label><input class="input" id="p-price" name="price" type="number" min="0" step="500" required></div>
        </div>
        <p><label class="check"><input type="checkbox" name="active" checked> Aktif</label></p>
      </div>
      <div class="modal-foot"><button type="button" class="btn ghost" data-close>Batal</button><button class="btn" type="submit">Simpan</button></div>
    </form>
  </dialog>
</main>
@endsection
