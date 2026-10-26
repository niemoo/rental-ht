<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-layout="vertical"
    data-topbar="light"
    data-sidebar="dark"
    data-sidebar-size="lg"
    data-sidebar-image="none"
    data-preloader="disable"
    data-bs-theme="light"
>
    <head>
        @include('partials.head')
    </head>
    <body x-data="appShell" x-bind:class="navOpen && 'vertical-sidebar-enable'">
        @php
            $navSections = [
                'Menu' => [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-grid', 'active' => 'dashboard', 'can' => null],
                    ['route' => 'units.index', 'label' => 'Unit', 'icon' => 'radio-tower', 'active' => 'units.*', 'can' => \App\Enums\Permission::ViewUnits],
                    ['route' => 'categories.index', 'label' => 'Kategori', 'icon' => 'package', 'active' => 'categories.*', 'can' => \App\Enums\Permission::ViewCategories],
                    ['route' => 'customers.index', 'label' => 'Pelanggan', 'icon' => 'users', 'active' => 'customers.*', 'can' => \App\Enums\Permission::ViewCustomers],
                    ['route' => 'rentals.index', 'label' => 'Riwayat', 'icon' => 'history', 'active' => ['rentals.index', 'rentals.show', 'rentals.edit', 'rentals.cancel', 'rentals.payments.*', 'rentals.charges.*'], 'can' => \App\Enums\Permission::ViewRentals],
                    ['route' => 'reports.revenue', 'label' => 'Laporan', 'icon' => 'chart-column', 'active' => 'reports.*', 'can' => \App\Enums\Permission::ViewReports],
                ],
                'Pengaturan' => [
                    ['route' => 'users.index', 'label' => 'Pengguna', 'icon' => 'user-cog', 'active' => 'users.*', 'can' => \App\Enums\Permission::ManageUsers],
                    ['route' => 'roles.index', 'label' => 'Roles', 'icon' => 'shield', 'active' => 'roles.*', 'can' => \App\Enums\Permission::ManageRoles],
                ],
            ];
            $navSections = array_filter(array_map(
                fn (array $links): array => array_filter($links, fn (array $link): bool => $link['can'] === null || auth()->user()->can($link['can'])),
                $navSections,
            ));
        @endphp

        <div id="layout-wrapper">
            <header id="page-topbar">
                <div class="layout-width">
                    <div class="navbar-header">
                        <div class="d-flex align-items-center">
                            <button
                                type="button"
                                class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
                                aria-label="Menu"
                                x-on:click="toggleNav()"
                            >
                                <span class="hamburger-icon"><span></span><span></span><span></span></span>
                            </button>
                        </div>

                        <div class="d-flex align-items-center">
                            <div class="ms-1 header-item d-flex">
                                <button
                                    type="button"
                                    class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                                    aria-label="Ganti tema"
                                    x-on:click="$store.theme.toggle()"
                                >
                                    <x-icon name="moon" class="fs-22" x-show="! $store.theme.dark" />
                                    <x-icon name="sun" class="fs-22" x-show="$store.theme.dark" x-cloak />
                                </button>
                            </div>

                            <div class="dropdown ms-sm-3 header-item topbar-user" x-data="{ menuOpen: false }" x-on:click.outside="menuOpen = false" x-on:keydown.escape.window="menuOpen = false">
                                <button type="button" class="btn" aria-haspopup="menu" x-bind:aria-expanded="menuOpen" x-on:click="menuOpen = ! menuOpen">
                                    <span class="d-flex align-items-center">
                                        <span class="avatar-xs">
                                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-semibold fs-12">{{ auth()->user()->initials() }}</span>
                                        </span>
                                        <span class="text-start ms-xl-2">
                                            <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ auth()->user()->name }}</span>
                                            <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">{{ auth()->user()->roleLabel() }}</span>
                                        </span>
                                    </span>
                                </button>
                                {{-- Velzon pins .topbar-user menus to top: 6px !important for Popper to shift; without Popper, drop it below the profile instead. --}}
                                <div class="dropdown-menu dropdown-menu-end show" style="top: 100% !important; right: 0; left: auto" x-show="menuOpen" x-cloak role="menu">
                                    <h6 class="dropdown-header">Halo, {{ auth()->user()->name }}</h6>
                                    <a href="{{ route('profile') }}" @class(['dropdown-item', 'active' => request()->routeIs('profile')]) role="menuitem">
                                        <x-icon name="user" class="text-muted fs-16 align-middle me-1" />
                                        <span class="align-middle">Profil saya</span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item" role="menuitem">
                                            <x-icon name="log-out" class="text-muted fs-16 align-middle me-1" />
                                            <span class="align-middle">Keluar</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="app-menu navbar-menu">
                <div class="navbar-brand-box">
                    <a href="{{ route('dashboard') }}" class="logo logo-light">
                        <span class="logo-sm"><x-brand compact /></span>
                        <span class="logo-lg"><x-brand /></span>
                    </a>
                </div>

                <div id="scrollbar" class="overflow-y-auto">
                    <div class="container-fluid">
                        <ul class="navbar-nav" id="navbar-nav">
                            @foreach ($navSections as $sectionLabel => $navLinks)
                                <li class="menu-title"><span>{{ $sectionLabel }}</span></li>
                                @foreach ($navLinks as $link)
                                    <li class="nav-item">
                                        <a
                                            href="{{ route($link['route']) }}"
                                            @class(['nav-link menu-link', 'active' => request()->routeIs(...(array) $link['active'])])
                                            @if (request()->routeIs(...(array) $link['active'])) aria-current="page" @endif
                                        >
                                            <x-icon :name="$link['icon']" />
                                            <span>{{ $link['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="sidebar-background"></div>
            </div>

            <div class="vertical-overlay" x-on:click="navOpen = false"></div>

            <div class="main-content">
                <div class="page-content">
                    <div class="container-fluid">
                        <x-flash />
                        {{ $slot }}
                    </div>
                </div>

                <footer class="footer">
                    <div class="container-fluid">
                        {{ now()->year }} &copy; {{ config('invoice.business.name') }}
                    </div>
                </footer>
            </div>
        </div>

        @livewireScriptConfig
    </body>
</html>
