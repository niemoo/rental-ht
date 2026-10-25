<div class="flex flex-col gap-5">
    <x-page-header title="Dasbor" :subtitle="now()->translatedFormat('l, j F')">
        <x-slot:action>
            @can(\App\Enums\Permission::CreateRentals)
                <x-button :href="route('rentals.create')"><x-icon name="plus" />Penyewaan baru</x-button>
            @endcan
        </x-slot:action>
    </x-page-header>

    {{-- Metric tiles --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-5">
        <x-stat-tile label="Total unit" :value="$fleet['total']" sub="di armada" icon="package" />
        <x-stat-tile label="Unit disewa" :value="$fleet['out_now']" :sub="'dari '.$fleet['total'].' unit'" icon="radio-tower" />
        <x-stat-tile label="Penyewaan bulan ini" :value="$monthStat['count']" :trend="$monthStat['change_pct']" icon="calendar-days" />
        <x-stat-tile label="Terlambat" :value="$overdueCount" sub="perlu ditindaklanjuti" icon="triangle-alert" />
    </div>

    {{-- Bar + gauge --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            <x-chart-card title="Penyewaan per bulan">
                <x-chart type="rentalsPerMonth" :data="$perMonth" />
            </x-chart-card>
        </div>
        <x-chart-card title="Pemakaian armada" subtitle="Porsi armada yang sedang disewa">
            <x-chart type="utilization" :data="['percent' => $utilization]" :height="300" />
        </x-chart-card>
    </div>

    {{-- Area --}}
    <x-chart-card title="Aktivitas penyewaan" subtitle="Unit keluar vs kembali, 4 minggu terakhir">
        <x-chart type="rentalActivity" :data="$activity" />
    </x-chart-card>

    {{-- Donut + out now --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="min-w-0 lg:col-span-5">
            <x-chart-card title="Status armada" :subtitle="$fleet['total'].' unit saat ini'">
                <x-chart type="fleetStatus" :data="['available' => $fleet['available'], 'onRent' => $fleet['on_rent'], 'overdue' => $fleet['overdue'], 'outOfService' => $fleet['out_of_service']]" :height="300" />
            </x-chart-card>
        </div>
        <div class="min-w-0 lg:col-span-7">
            <h2 class="mb-3 px-0.5 text-base font-semibold text-foreground">
                Sedang disewa <span class="text-sm font-medium tabular-nums text-muted-foreground">· {{ $outNow->total() }} aktif</span>
            </h2>
            <x-rentals.out-now :rentals="$outNow" :sort-by="$sortBy" :sort-direction="$sortDirection" />
        </div>
    </div>

    <x-rentals.upcoming :rentals="$upcoming" />
</div>
