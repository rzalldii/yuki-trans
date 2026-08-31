<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">

<title>@hasSection('title')@yield('title') | {{ config('app.name') }}@else{{ config('app.name') }}@endif</title>

<!-- Favicon -->
<link href="{{ asset('img/icon.svg') }}" rel="icon" type="image/svg+xml">

<!-- Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect">
<link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

<!-- Base Styles -->
<link href="{{ asset('vendor/fonts/boxicons.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/css/core.css') }}" class="template-customizer-core-css" rel="stylesheet">
<link href="{{ asset('vendor/css/theme-default.css') }}" class="template-customizer-theme-css" rel="stylesheet">
<link href="{{ asset('css/demo.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/libs/sweetalert2/sweetalert2.css') }}" rel="stylesheet">

@stack('style')