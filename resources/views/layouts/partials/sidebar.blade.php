@auth
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand justify-content-center" style="padding: 1.25rem;">
                    <a href="{{ route('dashboard') }}" class="app-brand-link" style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="app-brand-logo">
                            <img src="{{ asset('img/icon.svg') }}" alt="Truck Icon" width="36" height="36">
                        </span>
                        <span class="app-brand-text menu-text fw-bold text-uppercase" style="font-size: 1.15rem; letter-spacing: 0.5px;">
                            {{ config('app.name') }}
                        </span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none" aria-label="Close navigation">
                        <i class="bx bx-chevron-left bx-sm align-middle" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="menu-inner-shadow"></div>
                <ul class="menu-inner py-1">
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Overview</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle" aria-hidden="true"></i>
                            <div>Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Finance</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('finance-transactions.*') ? 'active' : '' }}">
                        <a href="{{ route('finance-transactions.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-receipt" aria-hidden="true"></i>
                            <div>Transactions</div>
                        </a>
                    </li>
                    @if (auth()->user()->isAdmin())
                        <li class="menu-item {{ request()->routeIs('finance-master-data.*', 'finance-wallets.*', 'finance-categories.*', 'finance-tags.*', 'finance-recurring.*') ? 'active' : '' }}">
                            <a href="{{ route('finance-master-data.index') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-layer" aria-hidden="true"></i>
                                <div>Master Data</div>
                            </a>
                        </li>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <li class="menu-header small text-uppercase">
                            <span class="menu-header-text">Administration</span>
                        </li>
                        <li class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <a href="{{ route('users.index') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-user-pin" aria-hidden="true"></i>
                                <div>Users</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                            <a href="{{ route('audit-logs.index') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-shield-quarter" aria-hidden="true"></i>
                                <div>Audit Logs</div>
                            </a>
                        </li>
                    @endif
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Account</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <a href="{{ route('profile.show') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-circle" aria-hidden="true"></i>
                            <div>Profile</div>
                        </a>
                    </li>
                </ul>
            </aside>
            <div class="layout-page">
@else
                <div class="layout-wrapper layout-content-navbar layout-without-menu">
                    <div class="layout-container">
                        <div class="layout-page">
            @endauth