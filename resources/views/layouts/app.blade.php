<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('layouts.partials.head')
</head>

<body>
    <a href="#main-content" class="visually-hidden-focusable">Skip to content</a>
    @include('layouts.partials.sidebar')
    @include('layouts.partials.navbar')
    <main id="main-content">
        @yield('content')
    </main>
    @include('layouts.partials.footer')
    @include('layouts.partials.script')
</body>

</html>