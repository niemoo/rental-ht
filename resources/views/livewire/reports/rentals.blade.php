@php use App\Support\RentalBill; @endphp
<div class="vstack gap-4">
    <x-reports.header
        active="rentals"
        :report="$report"
        :period-query="$this->reportPeriod->query()"
        :filter-query="$statuses === [] ? [] : ['status' => $statuses]"
        :period="$period"
        :from="$from"
        :to="$to"
    />

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Pemesanan" :value="$figures['count']" :sub="$figures['cancelled'] > 0 ? Number::format($figures['cancelled']).' dibatalkan ('.Number::format($figures['cancelled_pct']).'%)' : 'tidak ada yang batal'" icon="file-text" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Omzet" :value="RentalBill::rupiah($figures['omzet'])" sub="sudah atau belum dibayar" icon="chart-column" tone="success" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Rata-rata nilai" :value="RentalBill::rupiah($figures['average_value'])" sub="per pemesanan" icon="wallet" tone="info" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Rata-rata durasi" :value="Number::format($figures['average_days'], maxPrecision: 1).' hari'" sub="per pemesanan" icon="calendar-days" tone="warning" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-chart-card title="Pemesanan & omzet" :subtitle="($this->reportPeriod->groupsByDay() ? 'Per hari' : 'Per bulan').', menurut tanggal mulai sewa'">
                <x-chart type="rentalsAndOmzet" :data="$chart" />
            </x-chart-card>
        </div>
        <div class="col-xl-4">
            <x-chart-card title="Potongan & biaya tambahan" subtitle="Pada sewa periode ini">
                <table class="table table-borderless table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-0">Diskon diberikan</td>
                            <td class="text-end pe-0 fw-medium tabular-nums text-danger">−{{ RentalBill::rupiah($figures['discount']) }}</td>
                        </tr>
                        @foreach ($figures['charges'] as $type => $total)
                            <tr wire:key="charge-type-{{ $type }}" @class(['border-top border-top-dashed' => $loop->first])>
                                <td class="text-muted ps-0">{{ \App\Enums\ChargeType::from($type)->label() }}</td>
                                <td @class(['text-end pe-0 tabular-nums', 'fw-medium' => $total > 0, 'text-muted' => $total === 0])>{{ RentalBill::rupiah($total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-chart-card>
        </div>
    </div>

    <div class="card mb-0">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <h5 class="card-title mb-0 flex-grow-1">
                Daftar pemesanan <span class="text-muted fw-normal fs-13 tabular-nums">· {{ Number::format($rentals->total()) }}</span>
            </h5>
            <x-column-filter as="button" class="d-lg-none" label="Status" model="statuses" :options="$statusFilter['options']" :counts="$statusFilter['counts']" :selected="$statuses" />
            <x-sort-select
                class="d-lg-none"
                :options="['start' => 'Mulai', 'invoice' => 'Invoice', 'customer' => 'Pelanggan', 'days' => 'Durasi', 'units' => 'Unit', 'status' => 'Status', 'total' => 'Total', 'balance' => 'Sisa']"
                :sort-by="$sortBy"
                :sort-direction="$sortDirection"
            />
        </div>

        {{-- Desktop table --}}
        <div class="card-body d-none d-lg-block">
            <div class="table-responsive table-card">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <x-sort-header column="invoice" :sort-by="$sortBy" :sort-direction="$sortDirection">Invoice</x-sort-header>
                            <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                            <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Sewa</x-sort-header>
                            <x-sort-header column="days" :sort-by="$sortBy" :sort-direction="$sortDirection">Hari</x-sort-header>
                            <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                            <x-column-filter label="Status" model="statuses" :options="$statusFilter['options']" :counts="$statusFilter['counts']" :selected="$statuses" column="status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                            <x-sort-header column="total" :sort-by="$sortBy" :sort-direction="$sortDirection" class="text-end">Total</x-sort-header>
                            <x-sort-header column="balance" :sort-by="$sortBy" :sort-direction="$sortDirection" class="text-end">Sisa</x-sort-header>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rentals as $rental)
                            @php
                                $timing = $rental->timing();
                            @endphp
                            <tr wire:key="report-rental-row-{{ $rental->id }}">
                                <td class="tabular-nums"><a href="{{ route('rentals.show', $rental) }}" class="fw-medium link-primary">{{ $rental->invoice_number }}</a></td>
                                <td>
                                    <div class="fw-medium">{{ $rental->customer_name }}</div>
                                    @if ($rental->event_name)
                                        <div class="text-muted fs-12 text-truncate" style="max-width: 14rem">{{ $rental->event_name }}</div>
                                    @endif
                                </td>
                                <td class="tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M Y') }}</td>
                                <td class="tabular-nums">{{ Number::format($rental->days) }}</td>
                                <td class="tabular-nums">{{ Number::format($rental->items_count) }}</td>
                                <td><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                                <td class="text-end tabular-nums">{{ RentalBill::rupiah($rental->total_amount) }}</td>
                                <td @class(['text-end tabular-nums', 'text-muted' => (int) $rental->balance === 0, 'fw-medium' => (int) $rental->balance !== 0])>{{ RentalBill::rupiah((int) $rental->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-empty-state>Tidak ada pemesanan di periode ini.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @forelse ($rentals as $rental)
                @php
                    $timing = $rental->timing();
                @endphp
                <li wire:key="report-rental-item-{{ $rental->id }}" class="list-group-item d-flex align-items-center gap-3 py-3">
                    <div class="flex-grow-1" style="min-width: 0">
                        <a href="{{ route('rentals.show', $rental) }}" class="d-block fw-medium text-truncate link-body-emphasis">{{ $rental->customer_name }}</a>
                        <div class="text-muted fs-12 text-truncate tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }} · {{ RentalBill::rupiah($rental->total_amount) }}</div>
                    </div>
                    <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada pemesanan di periode ini.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($rentals->total() > 0)
            <div class="card-footer">{{ $rentals->links() }}</div>
        @endif
    </div>
</div>
