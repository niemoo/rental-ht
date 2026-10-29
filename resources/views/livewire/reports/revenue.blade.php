@php use App\Support\RentalBill; @endphp
<div class="vstack gap-4">
    <x-reports.header
        active="revenue"
        :report="$report"
        :period-query="$this->reportPeriod->query()"
        :filter-query="$methods === [] ? [] : ['method' => $methods]"
        :period="$period"
        :from="$from"
        :to="$to"
    />

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Omzet" :value="RentalBill::rupiah($figures['omzet'])" sub="nilai sewa periode ini" icon="chart-column" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Uang masuk bersih" :value="RentalBill::rupiah($figures['net'])" :sub="$figures['refunded'] > 0 ? 'setelah refund '.RentalBill::rupiah($figures['refunded']) : 'dari pembayaran'" icon="wallet" tone="success" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Uang masuk" :value="RentalBill::rupiah($figures['received'])" sub="sebelum refund" icon="arrow-down" tone="info" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-tile label="Omzet belum dibayar" :value="RentalBill::rupiah($figures['unpaid'])" sub="dari sewa periode ini" icon="clock" tone="warning" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-chart-card title="Uang masuk" :subtitle="($this->reportPeriod->groupsByDay() ? 'Per hari' : 'Per bulan').', bersih setelah refund'">
                <x-chart type="revenueByMethod" :data="$chart" />
            </x-chart-card>
        </div>
        <div class="col-xl-4">
            <x-chart-card title="Per metode" subtitle="Uang masuk bersih">
                <div class="vstack gap-3">
                    @foreach ($byMethod as $method => $total)
                        @php
                            $share = $figures['net'] > 0 ? max(0, round($total / $figures['net'] * 100)) : 0;
                        @endphp
                        <div wire:key="method-share-{{ $method }}">
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <span class="text-muted">{{ \App\Enums\PaymentMethod::from($method)->label() }}</span>
                                <span class="fw-medium tabular-nums">{{ RentalBill::rupiah($total) }}</span>
                            </div>
                            <div class="progress progress-sm" role="progressbar" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ \App\Enums\PaymentMethod::from($method)->label() }}">
                                <div class="progress-bar bg-primary" style="width: {{ $share }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-chart-card>
        </div>
    </div>

    <div class="card mb-0">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <h5 class="card-title mb-0 flex-grow-1">
                Pembayaran <span class="text-muted fw-normal fs-13 tabular-nums">· {{ Number::format($payments->total()) }}</span>
            </h5>
            <x-column-filter as="button" class="d-lg-none" label="Metode" model="methods" :options="$methodFilter['options']" :counts="$methodFilter['counts']" :selected="$methods" />
            <x-sort-select
                class="d-lg-none"
                :options="['date' => 'Tanggal', 'invoice' => 'Invoice', 'customer' => 'Pelanggan', 'method' => 'Metode', 'amount' => 'Jumlah']"
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
                            <x-sort-header column="date" :sort-by="$sortBy" :sort-direction="$sortDirection">Tanggal</x-sort-header>
                            <x-sort-header column="invoice" :sort-by="$sortBy" :sort-direction="$sortDirection">Invoice</x-sort-header>
                            <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                            <x-column-filter label="Metode" model="methods" :options="$methodFilter['options']" :counts="$methodFilter['counts']" :selected="$methods" column="method" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                            <th>Catatan</th>
                            <x-sort-header column="amount" :sort-by="$sortBy" :sort-direction="$sortDirection" class="text-end">Jumlah</x-sort-header>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr wire:key="payment-row-{{ $payment->id }}">
                                <td class="tabular-nums">{{ $payment->paid_at->translatedFormat('j M Y') }}</td>
                                <td class="tabular-nums"><a href="{{ route('rentals.show', $payment->rental_id) }}" class="fw-medium link-primary">{{ $payment->invoice_number }}</a></td>
                                <td>{{ $payment->customer_name }}</td>
                                <td>{{ $payment->method->label() }}</td>
                                <td class="text-muted text-truncate" style="max-width: 16rem">
                                    {{ $payment->isRefund() ? 'Pengembalian dana' : '' }}{{ $payment->isRefund() && $payment->note ? ' · ' : '' }}{{ $payment->note }}
                                </td>
                                <td @class(['text-end fw-medium tabular-nums', 'text-primary' => $payment->isRefund()])>{{ RentalBill::rupiah($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state>Tidak ada pembayaran di periode ini.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @forelse ($payments as $payment)
                <li wire:key="payment-item-{{ $payment->id }}" class="list-group-item d-flex align-items-center gap-3 py-3">
                    <div class="flex-grow-1" style="min-width: 0">
                        <a href="{{ route('rentals.show', $payment->rental_id) }}" class="d-block fw-medium text-truncate link-body-emphasis">{{ $payment->customer_name }}</a>
                        <div class="text-muted fs-12 text-truncate tabular-nums">
                            {{ $payment->paid_at->translatedFormat('j M') }} · {{ $payment->isRefund() ? 'Pengembalian dana' : $payment->method->label() }} · {{ $payment->invoice_number }}
                        </div>
                    </div>
                    <span @class(['fw-medium tabular-nums', 'text-primary' => $payment->isRefund()])>{{ RentalBill::rupiah($payment->amount) }}</span>
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada pembayaran di periode ini.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($payments->total() > 0)
            <div class="card-footer">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
