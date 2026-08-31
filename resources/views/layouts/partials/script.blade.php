<!-- Base Scripts -->
<script src="{{ asset('vendor/js/helpers.js') }}"></script>
<script src="{{ asset('js/config.js') }}"></script>
<script src="{{ asset('vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('vendor/js/menu.js') }}"></script>
<script src="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('vendor/libs/sweetalert2/sweetalert2.all.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>

<!-- Toast Script -->
<script nonce="{{ $cspNonce }}">
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
    });
</script>

@if (session('toast'))
    <script nonce="{{ $cspNonce }}">
        const toast = @json(session('toast'));
        Toast.fire({
            icon: toast.icon,
            title: toast.title
        });
    </script>
@endif

@stack('script')