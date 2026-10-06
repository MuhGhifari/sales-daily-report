<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#ffffff">
<title>{{ $title }} · Laporan SPG</title>
<link rel="icon" type="image/png" href="{{ asset_v('assets/brand/favicon.png') }}">
<link rel="stylesheet" href="{{ asset_v('assets/css/style.css') }}">
</head>
<body data-root="{{ url('/') }}/"@isset($bodyClass) class="{{ $bodyClass }}"@endisset>
@yield('content')
@php
    // Settings for assets/js/data.js, and the logged-in user's data (null on the login page when signed out)
    $appConfig = ['backend' => 'laravel', 'api' => url('api'), 'cleanUrls' => true, 'demoAccounts' => (bool) config('app.demo_accounts')];
@endphp
<script>
window.APP_CONFIG = @json($appConfig);
window.APP_BOOTSTRAP = @json($bootstrap ?? null);
</script>
<script src="{{ asset_v('assets/js/data.js') }}"></script>
<script src="{{ asset_v('assets/js/common.js') }}"></script>
<script src="{{ asset_v('assets/js/pages/'.$script.'.js') }}"></script>
</body>
</html>
