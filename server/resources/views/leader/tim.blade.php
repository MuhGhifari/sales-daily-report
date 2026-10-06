@extends('layouts.app', ['title' => 'Tim', 'script' => 'leader-tim'])

@section('content')
<main>
  <div class="page-head"><div><h1>SPG</h1><p class="sub" id="sub"></p></div><div class="actions"><span class="muted" id="count"></span><button type="button" class="btn" id="openAdd"><span data-icon="plus"></span>Tambah SPG</button></div></div>
  <section><div class="table-wrap"><table id="table"></table></div></section>
  <dialog class="modal" id="dlg" aria-labelledby="dlgTitle">
    <form id="form" autocomplete="off">
      <div class="modal-head"><h3 id="dlgTitle">Tambah SPG</h3><button type="button" class="icon-btn" data-close aria-label="Tutup" data-icon="x"></button></div>
      <div class="modal-body">
        <p class="error" id="err" hidden></p>
        <div class="form-grid">
          <div class="field"><label for="f-name">Nama</label><input class="input" id="f-name" name="name" required></div>
          <div class="field"><label for="f-phone">No. HP (untuk login)</label><input class="input" id="f-phone" name="phone" type="tel" inputmode="tel" placeholder="0812 3456 7890" required></div>
        </div>
        <p class="note" style="margin:0">Password awal: spg123. Toko dipilih SPG sendiri saat mulai shift.</p>
      </div>
      <div class="modal-foot"><button type="button" class="btn ghost" data-close>Batal</button><button class="btn" type="submit">Tambah</button></div>
    </form>
  </dialog>
</main>
@endsection
