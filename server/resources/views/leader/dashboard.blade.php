@extends('layouts.app', ['title' => 'Dashboard Tim', 'script' => 'leader-dashboard'])

@section('content')
<main>
  <div class="page-head">
    <div><h1 id="title"></h1><p class="sub" id="sub"></p></div>
    <div class="tabs" id="tabs" role="tablist">
      <button type="button" data-p="day">Hari ini</button>
      <button type="button" data-p="week">Minggu ini</button>
      <button type="button" data-p="month" class="on">Bulan ini</button>
    </div>
  </div>
  <div class="stats" id="stats"></div>
  <section>
    <h2>Tren penjualan</h2>
    <div id="trend"></div>
    <div class="legend"><span><i></i>Penjualan</span><span><i class="dash"></i>Target</span></div>
  </section>
  <section id="missingWrap" hidden>
    <h2>Belum mulai shift</h2>
    <div class="rows" id="missing"></div>
  </section>
  <div class="podium" id="podium"></div>
  <section>
    <div class="actions" style="justify-content:space-between;margin-bottom:8px"><h2 style="margin:0">Peringkat SPG</h2><button type="button" class="icon-btn" id="export" aria-label="Export CSV" data-icon="download"></button></div>
    <div id="spgs"></div>
  </section>
  <section>
    <h2>Per toko</h2>
    <div class="rows" id="stores"></div>
  </section>
  <section>
    <h2>Per produk</h2>
    <div class="pgrid" id="products"></div>
  </section>
</main>
@endsection
