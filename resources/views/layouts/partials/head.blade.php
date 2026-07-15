<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title')</title>

<link rel="icon" type="image/x-icon" href="{{ asset('img/favicon/favicon.ico') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet">

<link rel="stylesheet" href="{{ asset('vendor/fonts/boxicons.css') }}">

<link rel="stylesheet" href="{{ asset('vendor/css/core.css') }}" class="template-customizer-core-css">

<link rel="stylesheet" href="{{ asset('vendor/css/theme-default.css') }}" class="template-customizer-theme-css">

<link rel="stylesheet" href="{{ asset('css/demo.css') }}">

<link rel="stylesheet" href="{{ asset('vendor/libs/apex-charts/apex-charts.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">

<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/libs/sweetalert2/sweetalert2.css') }}">

<link rel="stylesheet" href="{{ asset('vendor/css/pages/page-auth.css') }}">

<script src="{{ asset('vendor/js/helpers.js') }}"></script>
<script src="{{ asset('js/config.js') }}"></script>