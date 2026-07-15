@extends('layouts.auth')
@section('title')
    Sign In | Project
@endsection
@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center">
                            <a href="{{ route('dashboard') }}" class="app-brand-link gap-2">
                                <span class="app-brand-logo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#696cff"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M19.1 7.8c-.38-.5-.97-.8-1.6-.8H15V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2 0 1.65 1.35 3 3 3s3-1.35 3-3h4c0 1.65 1.35 3 3 3s3-1.35 3-3c1.1 0 2-.9 2-2v-3.67c0-.43-.14-.86-.4-1.2zM17.5 9l1.5 2h-4V9zM7 19a1.003 1.003 0 0 1-.87-1.5c.37-.63 1.36-.63 1.73 0 .09.15.13.32.13.49 0 .55-.45 1-1 1Zm2.23-3s-.05-.05-.08-.07c-.06-.06-.12-.11-.17-.16-.12-.11-.25-.21-.38-.29a3 3 0 0 0-.67-.32c-.07-.02-.14-.05-.21-.07Q7.375 15 7 15c-.375 0-.49.04-.72.09-.07.02-.14.05-.21.07-.16.05-.31.11-.45.19-.07.04-.15.08-.22.13-.13.09-.26.18-.38.29-.06.05-.12.1-.18.16-.02.03-.05.04-.08.07h-.77V6h9v10H9.22ZM17 19a1.003 1.003 0 0 1-.87-1.5c.37-.63 1.36-.63 1.73 0 .09.15.13.32.13.49 0 .55-.45 1-1 1Zm3-3h-.77s-.05-.05-.08-.07c-.06-.06-.12-.11-.17-.16-.12-.11-.25-.21-.38-.29a3 3 0 0 0-.67-.32c-.07-.02-.14-.05-.21-.07Q17.375 15 17 15c-.375 0-.47.04-.7.09-.06.01-.12.03-.18.05-.18.06-.36.13-.52.22l-.12.06c-.17.1-.33.21-.48.35v-2.76h5v3Z">
                                        </path>
                                    </svg>
                                </span>
                                <span class="app-brand-text menu-text fw-bold text-uppercase"
                                    style="font-size: 1.15rem; letter-spacing: 0.5px; color: #566a7f;">
                                    Project
                                </span>
                            </a>
                        </div>
                        <form id="formAuthentication" class="mb-3" action="{{ route('login.post') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                    id="username" name="username" value="{{ old('username') }}" placeholder="Username">
                                @error('username')
                                    <div class="invalid-feedback d-block" id="usernameError"
                                        data-lockout="{{ $lockoutSeconds ?? '' }}">{{ $message }}</div>
                                @enderror
                                @if (!$errors->has('username') && $lockoutSeconds)
                                    <div class="invalid-feedback d-block" id="usernameLockout"
                                        data-lockout="{{ $lockoutSeconds }}">
                                        Too many failed login attempts. Please try again in {{ $lockoutSeconds }} seconds.
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
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Remember Me</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit" id="btnLogin">
                                    <span class="d-flex align-items-center justify-content-center gap-2">
                                        <i class="bx bx-log-in me-1"></i>Sign In
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
            var $lockoutEl = $('#usernameLockout').length ? $('#usernameLockout') : $('#usernameError');
            var lockoutSeconds = parseInt($lockoutEl.data('lockout'), 10);
            if (lockoutSeconds > 0) {
                var remaining = lockoutSeconds;
                var originalMessage = 'Too many failed login attempts. Please try again in';
                $('#btnLogin, #username, #password').prop('disabled', true);
                function updateCountdown() {
                    $lockoutEl.text(originalMessage + ' ' + remaining + ' seconds.');
                    if (remaining <= 0) {
                        clearInterval(countdownInterval);
                        $lockoutEl.text('').removeClass('d-block');
                        $('#username').removeClass('is-invalid');
                        $('#btnLogin, #username, #password').prop('disabled', false);
                        return;
                    }
                    remaining--;
                }
                updateCountdown();
                var countdownInterval = setInterval(updateCountdown, 1000);
            }
        });
    </script>
@endpush