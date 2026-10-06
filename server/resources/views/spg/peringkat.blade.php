@extends('layouts.app', ['title' => 'Peringkat', 'script' => 'spg-peringkat'])

@section('content')
<main>
  <div class="page-head"><div><h1>Peringkat</h1><p class="sub" id="sub"></p></div></div>
  <div style="margin-bottom:16px">
    <div class="tabs" id="tabs" role="tablist">
      <button type="button" data-p="day">Hari ini</button>
      <button type="button" data-p="week">Minggu ini</button>
      <button type="button" data-p="month" class="on">Bulan ini</button>
    </div>
  </div>
  <div class="podium" id="podium"></div>
  <section class="rows rows-card" id="list"></section>
</main>
@endsection
