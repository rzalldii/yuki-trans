@extends('layouts.auth')
@section('title', 'Login')
@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center">
                            <a href="{{ route('dashboard') }}" class="app-brand-link gap-2">
                                <span class="app-brand-logo">
                                    <img src="{{ asset('img/icon.svg') }}" alt="Truck Icon" width="36" height="36">
                                </span>
                                <span class="app-brand-text menu-text fw-bold text-uppercase"
                                    style="font-size: 1.15rem; letter-spacing: 0.5px; color: #566a7f;">
                                    Yuki Trans
                                </span>
                            </a>
                        </div>
                        <h4 class="mb-2">Welcome to Yuki Trans!</h4>
                        <p class="mb-4">Please log in to your account to continue.</p>
                        <form id="formAuthentication" class="mb-3" action="{{ route('login.post') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text"
                                    class="form-control @error('username') is-invalid @enderror {{ $lockoutSeconds ? 'is-invalid' : '' }}"
                                    id="username" name="username" value="{{ old('username') }}" placeholder="e.g., johndoe123"
                                    oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9_.]/g, '')" autofocus>
                                @if ($lockoutSeconds)
                                    <div class="invalid-feedback d-block" id="usernameLockout"
                                        data-lockout="{{ $lockoutSeconds }}">
                                        Too many failed login attempts. Please try again in {{ $lockoutSeconds }} seconds.
                                    </div>
                                @elseif ($errors->has('username'))
                                    <div class="invalid-feedback d-block" id="usernameError">
                                        {{ $errors->first('username') }}
                                    </div>
                                @endif
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label for="password" class="form-label">Password</label>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password" placeholder="············"
                                        aria-describedby="password">
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 d-flex justify-content-between">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">Remember Me</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit" id="btnLogin">
                                    <span class="d-flex align-items-center justify-content-center gap-2">
                                        <i class="bx bx-log-in me-1"></i>Login
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        $(document).ready(function () {
            var $lockoutEl = $('#usernameLockout');
            var lockoutSeconds = parseInt($lockoutEl.data('lockout'), 10);
            var countdownInterval = null;
            if (!isNaN(lockoutSeconds) && lockoutSeconds > 0) {
                var remaining = lockoutSeconds;
                var originalMessage = 'Too many failed login attempts. Please try again in';
                $('#username, #password, #remember, #btnLogin').prop('disabled', true);
                function updateCountdown() {
                    $lockoutEl.text(originalMessage + ' ' + remaining + ' seconds.');
                    if (remaining <= 0) {
                        if (countdownInterval) {
                            clearInterval(countdownInterval);
                        }
                        $lockoutEl.text('').removeClass('d-block');
                        $('#username').removeClass('is-invalid');
                        $('#username, #password, #remember, #btnLogin').prop('disabled', false);
                        return;
                    }
                    remaining--;
                }
                updateCountdown();
                countdownInterval = setInterval(updateCountdown, 1000);
            }
            $('#formAuthentication').on('submit', function () {
                $('#btnLogin')
                    .html('<span class="d-flex align-items-center justify-content-center gap-2"><i class="bx bx-loader-alt bx-spin"></i>Logging in...</span>')
                    .prop('disabled', true);
            });
        });
    </script>
@endpush