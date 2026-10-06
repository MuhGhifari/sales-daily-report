@extends('layouts.app', ['title' => 'Riwayat', 'script' => 'spg-riwayat'])

@section('content')
<main>
  <div class="page-head">
    <div><h1>Riwayat</h1><p class="sub" id="summary"></p></div>
    <div class="actions"><button type="button" class="btn ghost small" id="prev" aria-label="Bulan sebelumnya"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button><b id="month"></b><button type="button" class="btn ghost small" id="next" aria-label="Bulan berikutnya"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button></div>
  </div>
  <section>
    <h2>Tren penjualan</h2>
    <div id="trend"></div>
    <div class="legend"><span><i></i>Penjualan</span><span><i class="dash"></i>Target</span></div>
  </section>
  <section class="rows rows-card" id="list"></section>
</main>
@endsection
