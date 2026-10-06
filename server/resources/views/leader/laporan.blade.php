@extends('layouts.app', ['title' => 'Laporan', 'script' => 'leader-laporan'])

@section('content')
<main>
  <div class="page-head"><div><h1>Laporan</h1><p class="sub" id="sub"></p></div></div>
  <section>
  <div class="form-grid">
    <div class="field"><label for="spg">SPG</label><select class="input" id="spg"></select></div>
    <div class="field"><label for="store">Toko</label><select class="input" id="store"></select></div>
    <div class="field"><label for="from">Dari</label><input class="input" type="date" id="from"></div>
    <div class="field"><label for="to">Sampai</label><input class="input" type="date" id="to"></div>
  </div>
  </section>
  <section>
    <h2>Tren penjualan</h2>
    <div id="trend"></div>
    <div class="legend"><span><i></i>Penjualan</span><span><i class="dash"></i>Target</span></div>
  </section>
  <section>
    <p class="small muted" id="summary" style="margin-top:0"></p>
    <div class="table-wrap"><table id="table"></table></div>
  </section>
</main>
@endsection
