@extends('layouts.app', ['title' => 'Dashboard Area', 'script' => 'supervisor-dashboard'])

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
  <section class="flat">
    <h2>SPG terbaik area</h2>
    <div class="podium" id="podium"></div>
  </section>
  <section>
    <h2>Peringkat tim</h2>
    <div class="table-wrap"><table id="teams"></table></div>
  </section>
</main>
@endsection
