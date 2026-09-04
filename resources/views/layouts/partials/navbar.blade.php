<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
    @auth
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)" role="button" aria-label="Toggle navigation">
                <i class="bx bx-menu bx-sm" aria-hidden="true"></i>
            </a>
        </div>
    @endauth
    <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100" id="navbar-collapse">
        <div class="navbar-nav align-items-center">
            @auth
                @hasSection('breadcrumb')
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-style1 mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}">Home</a>
                            </li>
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                @else
                    <span class="navbar-text fw-semibold text-muted d-none d-md-inline-block">
                        Overview
                    </span>
                @endif
            @else
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                    <img src="{{ asset('img/icon.svg') }}" alt="Truck Icon" width="30" height="30">
                    <span class="fw-bold text-uppercase text-body fs-5">
                        {{ config('app.name') }}
                    </span>
                </a>
            @endauth
        </div>
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            @auth
                @php
                    $user = auth()->user();
                    $name = trim($user->full_name ?? $user->username ?? 'User');
                    $words = preg_split('/\s+/', $name);
                    if (count($words) >= 2) {
                        $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                    } else {
                        $initials = strtoupper(substr($name, 0, 2));
                    }
                @endphp
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a class="nav-link dropdown-toggle hide-arrow p-0 cursor-pointer" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="User profile" aria-expanded="false">
                        <div class="avatar avatar-online cursor-pointer">
                            <span class="avatar-initial rounded-circle bg-label-primary fw-semibold">
                                {{ $initials }}
                            </span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <div class="dropdown-item-text py-2">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar avatar-online">
                                            <span class="avatar-initial rounded-circle bg-label-primary fw-semibold">
                                                {{ $initials }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <span class="fw-semibold d-block text-truncate">
                                            {{ $user->full_name ?? $user->username }}
                                        </span>
                                        <small class="text-muted text-capitalize">
                                            <span class="badge {{ $user->isAdmin() ? 'bg-label-primary' : 'bg-label-secondary' }} badge-xs">
                                                {{ $user->role }}
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}">
                                <i class="bx bx-user me-2" aria-hidden="true"></i>
                                <span class="align-middle">My Profile</span>
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="m-0" id="logout-form">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center">
                                    <i class="bx bx-power-off me-2" aria-hidden="true"></i>
                                    <span class="align-middle">Log Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                <li class="nav-item">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-log-in me-1" aria-hidden="true"></i>Login
                    </a>
                </li>
            @endauth
        </ul>
    </div>
</nav>
<div class="content-wrapper">