<div class="vstack gap-4">
    <x-page-header title="Dashboard" :subtitle="now()->translatedFormat('l, j F Y')">
        <x-slot:action>
            @can(\App\Enums\Permission::CreateRentals)
                <x-button variant="success" :href="route('rentals.create')"><x-icon name="plus" />Penyewaan baru</x-button>
            @endcan
        </x-slot:action>
    </x-page-header>

    {{-- Metric tiles --}}
    <div class="row g-3">
        <div class="col-xl-3 col-sm-6">
            <x-stat-tile label="Total unit" :value="$fleet['total']" sub="di armada" icon="package" tone="primary" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-stat-tile label="Unit disewa" :value="$fleet['out_now']" :sub="'dari '.Number::format($fleet['total']).' unit'" icon="radio-tower" tone="info" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-stat-tile label="Penyewaan bulan ini" :value="$monthStat['count']" :trend="$monthStat['change_pct']" sub="dibanding bulan lalu" icon="calendar-days" tone="success" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-stat-tile label="Terlambat" :value="$overdueCount" sub="perlu ditindaklanjuti" icon="triangle-alert" :tone="$overdueCount > 0 ? 'danger' : 'success'" />
        </div>
    </div>

    {{-- Bar + gauge --}}
    <div class="row g-3">
        <div class="col-xl-8">
            <x-chart-card title="Penyewaan per bulan" subtitle="12 bulan terakhir">
                <x-chart type="rentalsPerMonth" :data="$perMonth" />
            </x-chart-card>
        </div>
        <div class="col-xl-4">
            <x-chart-card title="Pemakaian armada" subtitle="Porsi armada yang sedang disewa">
                <x-chart type="utilization" :data="['percent' => $utilization]" :height="300" />
                <p class="text-center text-muted mb-0 tabular-nums">{{ Number::format($fleet['out_now']) }} dari {{ Number::format($fleet['total']) }} unit sedang di luar</p>
            </x-chart-card>
        </div>
    </div>

    {{-- Area --}}
    <x-chart-card title="Aktivitas penyewaan" subtitle="Unit keluar vs kembali, 4 minggu terakhir">
        <x-chart type="rentalActivity" :data="$activity" />
    </x-chart-card>

    {{-- Donut + out now --}}
    <div class="row g-3">
        <div class="col-xl-5">
            <x-chart-card title="Status armada" :subtitle="Number::format($fleet['total']).' unit saat ini'">
                <x-chart type="fleetStatus" :data="['available' => $fleet['available'], 'onRent' => $fleet['on_rent'], 'overdue' => $fleet['overdue'], 'outOfService' => $fleet['out_of_service']]" :height="300" />
            </x-chart-card>
        </div>
        <div class="col-xl-7">
            <x-rentals.out-now :rentals="$outNow" :sort-by="$sortBy" :sort-direction="$sortDirection" />
        </div>
    </div>

    <x-rentals.upcoming :rentals="$upcoming" />
</div>
