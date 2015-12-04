<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@section('title') Laravel Karma @show</title>

    <script>var config = {!! json_encode([
        'csrf' => csrf_token(),
        'user' => Auth::user()
    ]) !!};</script>
    <script src="{{ asset_ts('assets/app.js') }}"></script>
    <link rel="stylesheet" href="{{ asset_ts('assets/app.css') }}" />

    <link rel="apple-touch-icon"         href="{{ asset_ts('favicon.png') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset_ts('favicon.ico') }}" />
    <link rel="icon" type="image/png"    href="{{ asset_ts('favicon.png') }}" />
</head>
<body>
    @yield('content')
    <script>
        (function(global) {
            (require('Application')).create();
        })(window);
    </script>
</body>
</html>
