@extends('layouts.app', ['title' => 'Beranda', 'script' => 'spg-beranda'])

@section('content')
<main>
  <div id="celebrate"></div>
  <div class="page-head"><div class="hello"><button type="button" class="photo-edit" id="photoBtn" aria-label="Ganti foto profil" title="Ganti foto profil"></button><div><h1 id="hello"></h1><p class="sub" id="sub"></p></div></div></div>
  <section class="today" id="today"></section>
  <div class="tiles" id="rank"></div>
  <section id="periods"></section>
</main>
@endsection
