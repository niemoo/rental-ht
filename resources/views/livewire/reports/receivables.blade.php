@php
    use App\Support\RentalBill;
    use App\Support\Reports\ReceivablesReport;

    $largest = max(1, ...array_column($figures['by_aging'], 'total'));
    $agingColors = [
        'upcoming' => 'var(--vz-secondary-color)',
        'running' => 'var(--vz-primary)',
        'days_0_7' => 'var(--vz-warning)',
        'days_8_30' => 'var(--vz-orange, #f1963b)',
        'days_over_30' => 'var(--vz-danger)',
    ];
@endphp
<div class="vstack gap-4">
    <x-reports.header
        active="receivables"
        :report="$report"
        :period-query="\App\Support\ReportPeriod::fromInput($period, $from, $to)->query()"
        :filter-query="$agings === [] ? [] : ['aging' => $agings]"
    />

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Total piutang" :value="RentalBill::rupiah($figures['total'])" :sub="Number::format($figures['count']).' pemesanan'" icon="wallet" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Sudah selesai" :value="RentalBill::rupiah($figures['by_aging']['days_0_7']['total'] + $figures['by_aging']['days_8_30']['total'] + $figures['by_aging']['days_over_30']['total'])" sub="sewa selesai, belum lunas" icon="clock" tone="warning" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Lewat 30 hari" :value="RentalBill::rupiah($figures['by_aging']['days_over_30']['total'])" :sub="Number::format($figures['by_aging']['days_over_30']['count']).' pemesanan'" icon="triangle-alert" tone="danger" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Lebih bayar" :value="RentalBill::rupiah($figures['overpaid_total'])" :sub="Number::format($figures['overpaid_count']).' perlu dikembalikan'" icon="arrow-up" tone="info" /></div>
    </div>

    {{-- Aging: clicking a bar filters the table to that age. --}}
    <x-chart-card title="Umur piutang" subtitle="Dihitung dari tanggal kembali, atau tanggal batal. Klik salah satu untuk menyaring tabel.">
        <div class="vstack gap-1">
            @foreach (ReceivablesReport::AGINGS as $aging => $label)
                @php
                    $group = $figures['by_aging'][$aging];
                    $isDimmed = $agings !== [] && ! in_array($aging, $agings, true);
                @endphp
                <button
                    type="button"
                    wire:click="$set('agings', {{ in_array($aging, $agings, true) && count($agings) === 1 ? '[]' : "['".$aging."']" }})"
                    wire:key="aging-bar-{{ $aging }}"
                    @class(['aging-row btn btn-ghost-dark w-100 text-start fw-normal', 'opacity-50' => $isDimmed])
                    aria-pressed="{{ $agings === [$aging] ? 'true' : 'false' }}"
                >
                    <span class="text-muted text-truncate">{{ $label }}</span>
                    <span class="progress progress-sm">
                        <span class="progress-bar" style="width: {{ round($group['total'] / $largest * 100) }}%; background-color: {{ $agingColors[$aging] }}"></span>
                    </span>
                    <span class="text-end tabular-nums text-nowrap">
                        <span class="fw-medium text-body">{{ RentalBill::rupiah($group['total']) }}</span>
                        <span class="text-muted fs-12">· {{ Number::format($group['count']) }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </x-chart-card>

    <div class="card mb-0">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <h5 class="card-title mb-0 flex-grow-1">
                Belum lunas <span class="text-muted fw-normal fs-13 tabular-nums">· {{ Number::format($rentals->total()) }}</span>
            </h5>
            <x-column-filter as="button" class="d-lg-none" label="Umur" model="agings" :options="$agingFilter['options']" :counts="$agingFilter['counts']" :selected="$agings" />
            <x-sort-select
                class="d-lg-none"
                :options="['balance' => 'Sisa', 'invoice' => 'Invoice', 'customer' => 'Pelanggan', 'due' => 'Kembali', 'aging' => 'Umur']"
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
                            <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Sewa</x-sort-header>
                            <x-column-filter label="Umur" model="agings" :options="$agingFilter['options']" :counts="$agingFilter['counts']" :selected="$agings" column="aging" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                            <x-sort-header column="total" :sort-by="$sortBy" :sort-direction="$sortDirection" class="text-end">Total</x-sort-header>
                            <x-sort-header column="paid" :sort-by="$sortBy" :sort-direction="$sortDirection" class="text-end">Dibayar</x-sort-header>
                            <x-sort-header column="balance" :sort-by="$sortBy" :sort-direction="$sortDirection" class="text-end">Sisa</x-sort-header>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rentals as $rental)
                            @php
                                $days = $report->daysOwed($rental);
                            @endphp
                            <tr wire:key="receivable-row-{{ $rental->id }}">
                                <td class="tabular-nums"><a href="{{ route('rentals.show', $rental) }}" class="fw-medium link-primary">{{ $rental->invoice_number }}</a></td>
                                <td>
                                    <div class="fw-medium">{{ $rental->customer_name }}</div>
                                    <div class="text-muted fs-12 tabular-nums">{{ $rental->customer->phone }}</div>
                                </td>
                                <td class="tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M Y') }}</td>
                                <td>
                                    {{ ReceivablesReport::AGINGS[$rental->aging] }}
                                    @if ($days > 0)
                                        <span class="text-muted fs-12 tabular-nums">· {{ Number::format($days) }} hari</span>
                                    @endif
                                </td>
                                <td class="text-end tabular-nums">{{ RentalBill::rupiah($rental->total_amount) }}</td>
                                <td class="text-end tabular-nums">{{ RentalBill::rupiah($rental->paid_amount) }}</td>
                                <td class="text-end fw-semibold tabular-nums">{{ RentalBill::rupiah((int) $rental->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty-state>Tidak ada piutang. Semua tagihan sudah lunas.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @forelse ($rentals as $rental)
                <li wire:key="receivable-item-{{ $rental->id }}" class="list-group-item d-flex align-items-center gap-3 py-3">
                    <div class="flex-grow-1" style="min-width: 0">
                        <a href="{{ route('rentals.show', $rental) }}" class="d-block fw-medium text-truncate link-body-emphasis">{{ $rental->customer_name }}</a>
                        <div class="text-muted fs-12 text-truncate tabular-nums">{{ $rental->invoice_number }} · {{ ReceivablesReport::AGINGS[$rental->aging] }}</div>
                    </div>
                    <span class="fw-semibold tabular-nums">{{ RentalBill::rupiah((int) $rental->balance) }}</span>
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada piutang. Semua tagihan sudah lunas.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($rentals->total() > 0)
            <div class="card-footer">{{ $rentals->links() }}</div>
        @endif
    </div>

    @if ($overpaid->isNotEmpty())
        <div class="card mb-0">
            <div class="card-header">
                <h5 class="card-title mb-0">Lebih bayar <span class="text-muted fw-normal fs-13">· perlu dikembalikan</span></h5>
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($overpaid as $rental)
                    <li wire:key="overpaid-{{ $rental->id }}" class="list-group-item d-flex align-items-center gap-3 py-3">
                        <div class="flex-grow-1" style="min-width: 0">
                            <a href="{{ route('rentals.show', $rental) }}" class="d-block fw-medium text-truncate link-body-emphasis">{{ $rental->customer->name }}</a>
                            <div class="text-muted fs-12 tabular-nums">{{ $rental->invoice_number }} · total {{ RentalBill::rupiah($rental->total_amount) }}, dibayar {{ RentalBill::rupiah($rental->paid_amount) }}</div>
                        </div>
                        <span class="fw-semibold tabular-nums text-primary">{{ RentalBill::rupiah((int) $rental->overpaid) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
