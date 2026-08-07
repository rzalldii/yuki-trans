@auth
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand justify-content-center" style="padding: 1.25rem;">
                    <a href="{{ route('dashboard') }}" class="app-brand-link"
                        style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="app-brand-logo">
                            <img src="{{ asset('img/icon.svg') }}" alt="Truck Icon" width="36" height="36">
                        </span>
                        <span class="app-brand-text menu-text fw-bold text-uppercase"
                            style="font-size: 1.15rem; letter-spacing: 0.5px;">
                            Yuki Trans
                        </span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>
                <div class="menu-inner-shadow"></div>
                <ul class="menu-inner py-1">
                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home"></i>
                            <div data-i18n="Analytics">Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Pages</span>
                    </li>
                    @if (auth()->user()->isAdmin())
                        <li
                            class="menu-item {{ request()->routeIs('finance-categories.*', 'finance-transactions.*') ? 'active open' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon tf-icons bx bx-receipt"></i>
                                <div data-i18n="Finance">Finance</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item {{ request()->routeIs('finance-categories.*') ? 'active' : '' }}">
                                    <a href="{{ route('finance-categories.index') }}" class="menu-link">
                                        <div data-i18n="Categories">Categories</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('finance-transactions.*') ? 'active' : '' }}">
                                    <a href="{{ route('finance-transactions.index') }}" class="menu-link">
                                        <div data-i18n="Transactions">Transactions</div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="menu-item {{ request()->routeIs('finance-transactions.*') ? 'active' : '' }}">
                            <a href="{{ route('finance-transactions.index') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-receipt"></i>
                                <div data-i18n="Finance">Finance</div>
                            </a>
                        </li>
                    @endif
                    <li class="menu-item {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                        <a href="{{ route('profile.show') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div data-i18n="Profile">Profile</div>
                        </a>
                    </li>
                    @if (auth()->user()->isAdmin())
                        <li class="menu-header small text-uppercase">
                            <span class="menu-header-text">Admin</span>
                        </li>
                        <li class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <a href="{{ route('users.index') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-group"></i>
                                <div data-i18n="Users">Users</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                            <a href="{{ route('audit-logs.index') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-history"></i>
                                <div data-i18n="Audit Logs">Audit Logs</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </aside>
            <div class="layout-page">
@else
                <div class="layout-wrapper layout-content-navbar layout-without-menu">
                    <div class="layout-container">
                        <div class="layout-page">
            @endauth