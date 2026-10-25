<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen" x-data="{ navOpen: false }">
        @php
            $navSections = [
                'MENU' => [
                    ['route' => 'dashboard', 'label' => 'Dasbor', 'icon' => 'layout-grid', 'active' => 'dashboard', 'can' => null],
                    ['route' => 'units.index', 'label' => 'Unit', 'icon' => 'radio-tower', 'active' => 'units.*', 'can' => \App\Enums\Permission::ViewUnits],
                    ['route' => 'categories.index', 'label' => 'Kategori', 'icon' => 'package', 'active' => 'categories.*', 'can' => \App\Enums\Permission::ViewCategories],
                    ['route' => 'customers.index', 'label' => 'Pelanggan', 'icon' => 'users', 'active' => 'customers.*', 'can' => \App\Enums\Permission::ViewCustomers],
                    ['route' => 'rentals.index', 'label' => 'Riwayat', 'icon' => 'history', 'active' => ['rentals.index', 'rentals.show', 'rentals.edit', 'rentals.cancel', 'rentals.payments.*', 'rentals.charges.*'], 'can' => \App\Enums\Permission::ViewRentals],
                    ['route' => 'reports.revenue', 'label' => 'Laporan', 'icon' => 'chart-column', 'active' => 'reports.*', 'can' => \App\Enums\Permission::ViewReports],
                ],
                'PENGATURAN' => [
                    ['route' => 'users.index', 'label' => 'Pengguna', 'icon' => 'user-cog', 'active' => 'users.*', 'can' => \App\Enums\Permission::ManageUsers],
                    ['route' => 'roles.index', 'label' => 'Roles', 'icon' => 'shield', 'active' => 'roles.*', 'can' => \App\Enums\Permission::ManageRoles],
                ],
            ];
            $navSections = array_filter(array_map(
                fn (array $links): array => array_filter($links, fn (array $link): bool => $link['can'] === null || auth()->user()->can($link['can'])),
                $navSections,
            ));
        @endphp

        <aside
            class="fixed inset-y-0 left-0 z-50 w-[290px] -translate-x-full overflow-y-auto border-r border-border bg-card px-5 pb-6 transition-transform duration-300 lg:translate-x-0"
            x-bind:class="navOpen && 'translate-x-0'"
        >
            <div class="flex h-20 items-center">
                <a href="{{ route('dashboard') }}"><x-brand /></a>
            </div>
            @foreach ($navSections as $sectionLabel => $navLinks)
            <div class="mt-4 mb-2 px-3 text-[11px] font-semibold tracking-wider text-muted-foreground">{{ $sectionLabel }}</div>
            <nav class="flex flex-col gap-1" aria-label="{{ $sectionLabel }}">
                @foreach ($navLinks as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-[14.5px] font-medium transition-colors',
                            'bg-accent text-primary' => request()->routeIs(...(array) $link['active']),
                            'text-muted-foreground hover:bg-muted hover:text-foreground' => ! request()->routeIs(...(array) $link['active']),
                        ])
                    >
                        <x-icon :name="$link['icon']" class="size-5" />
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
            @endforeach
        </aside>

        <div x-show="navOpen" x-cloak x-on:click="navOpen = false" class="fixed inset-0 z-40 bg-black/45 lg:hidden"></div>

        <div class="lg:pl-[290px]">
            <header class="sticky top-0 z-30 flex h-[72px] items-center gap-3 border-b border-border bg-card px-4" style="padding-top: env(safe-area-inset-top, 0px)">
                <button type="button" aria-label="Menu" x-on:click="navOpen = true" class="grid size-11 place-items-center rounded-lg border border-border text-muted-foreground hover:bg-muted lg:hidden">
                    <x-icon name="menu" class="size-5" />
                </button>

                <div class="ml-auto flex items-center gap-3">
                    <button
                        type="button"
                        aria-label="Ganti tema"
                        x-on:click="$store.theme.toggle()"
                        class="grid size-11 place-items-center rounded-full border border-border bg-card text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    >
                        <x-icon name="moon" class="size-5" x-show="! $store.theme.dark" />
                        <x-icon name="sun" class="size-5" x-show="$store.theme.dark" x-cloak />
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Keluar" aria-label="Keluar" class="grid size-11 place-items-center rounded-full border border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground">
                            <x-icon name="log-out" class="size-[17px]" />
                        </button>
                    </form>

                    <div class="flex items-center gap-2.5 pl-1">
                        <span class="hidden text-sm font-semibold text-foreground sm:block">{{ auth()->user()->name }}</span>
                        <span class="grid size-10 place-items-center rounded-full bg-accent text-xs font-semibold text-primary">{{ auth()->user()->initials() }}</span>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-[1360px] px-4 py-5 lg:px-8 lg:py-7">
                <x-flash />
                {{ $slot }}
            </main>
        </div>

        @livewireScriptConfig
    </body>
</html>
