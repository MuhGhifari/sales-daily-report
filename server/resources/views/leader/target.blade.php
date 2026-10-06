@extends('layouts.app', ['title' => 'Target', 'script' => 'leader-target'])

@section('content')
<main>
  <div class="page-head">
    <div><h1>Target</h1><p class="sub" id="sub"></p></div>
    <div class="actions"><button type="button" class="btn ghost small" id="prev" aria-label="Bulan sebelumnya"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button><b id="month"></b><button type="button" class="btn ghost small" id="next" aria-label="Bulan berikutnya"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button></div>
  </div>
<section>
  <div class="actions" style="margin-bottom:12px">
    <button type="button" class="link" id="copyPrev">Salin bulan lalu</button>
    <button type="button" class="link" id="applyAll">Samakan semua</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>SPG</th><th class="num">Bulanan</th><th class="num">Mingguan</th><th class="num">Harian</th><th>Diatur oleh</th></tr></thead>
      <tbody id="rows"></tbody>
    </table>
  </div>
  <p class="note">Mingguan dan harian dihitung otomatis. Isi hanya jika ingin diubah manual.</p>
  <p><button type="button" class="btn" id="save">Simpan</button></p>
</section>
</main>
@endsection
