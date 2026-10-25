@php use App\Support\RentalBill; @endphp
<div class="flex flex-col gap-5">
    <x-reports.header
        active="revenue"
        :report="$report"
        :period-query="$this->reportPeriod->query()"
        :filter-query="$methods === [] ? [] : ['method' => $methods]"
        :period="$period"
        :from="$from"
        :to="$to"
    />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-5">
        <x-stat-tile label="Omzet" :value="RentalBill::rupiah($figures['omzet'])" sub="nilai sewa periode ini" />
        <x-stat-tile label="Uang masuk bersih" :value="RentalBill::rupiah($figures['net'])" :sub="$figures['refunded'] > 0 ? 'setelah refund '.RentalBill::rupiah($figures['refunded']) : 'dari pembayaran'" />
        <x-stat-tile label="Uang masuk" :value="RentalBill::rupiah($figures['received'])" sub="sebelum refund" />
        <x-stat-tile label="Omzet belum dibayar" :value="RentalBill::rupiah($figures['unpaid'])" sub="dari sewa periode ini" />
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            <x-chart-card title="Uang masuk" :subtitle="($this->reportPeriod->groupsByDay() ? 'Per hari' : 'Per bulan').', bersih setelah refund'">
                <x-chart type="revenueByMethod" :data="$chart" />
            </x-chart-card>
        </div>
        <x-chart-card title="Per metode" subtitle="Uang masuk bersih">
            <dl class="flex flex-col gap-3 text-sm">
                @foreach ($byMethod as $method => $total)
                    @php($share = $figures['net'] > 0 ? max(0, round($total / $figures['net'] * 100)) : 0)
                    <div wire:key="method-share-{{ $method }}">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">{{ \App\Enums\PaymentMethod::from($method)->label() }}</dt>
                            <dd class="font-medium tabular-nums">{{ RentalBill::rupiah($total) }}</dd>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $share }}%"></div>
                        </div>
                    </div>
                @endforeach
            </dl>
        </x-chart-card>
    </div>

    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <h2 class="text-base font-semibold">Pembayaran <span class="text-sm font-medium tabular-nums text-muted-foreground">· {{ $payments->total() }}</span></h2>
            <div class="flex gap-2.5 lg:hidden">
                <x-column-filter as="button" label="Metode" model="methods" :options="$methodFilter['options']" :counts="$methodFilter['counts']" :selected="$methods" />
                <x-sort-select
                    :options="['date' => 'Tanggal', 'invoice' => 'Invoice', 'customer' => 'Pelanggan', 'method' => 'Metode', 'amount' => 'Jumlah']"
                    :sort-by="$sortBy"
                    :sort-direction="$sortDirection"
                />
            </div>
        </div>

        {{-- Desktop table --}}
        <x-card class="hidden lg:block">
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <x-sort-header column="date" :sort-by="$sortBy" :sort-direction="$sortDirection">Tanggal</x-sort-header>
                        <x-sort-header column="invoice" :sort-by="$sortBy" :sort-direction="$sortDirection">Invoice</x-sort-header>
                        <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                        <x-column-filter label="Metode" model="methods" :options="$methodFilter['options']" :counts="$methodFilter['counts']" :selected="$methods" column="method" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                        <th class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap">Catatan</th>
                        <x-sort-header column="amount" :sort-by="$sortBy" :sort-direction="$sortDirection" class="pr-4 text-right">Jumlah</x-sort-header>
                    </tr>
                </thead>
                <tbody class="[&_tr:last-child]:border-0">
                    @forelse ($payments as $payment)
                        <tr wire:key="payment-row-{{ $payment->id }}" class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-2 pl-4 align-middle whitespace-nowrap tabular-nums">{{ $payment->paid_at->translatedFormat('j M Y') }}</td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">
                                <a href="{{ route('rentals.show', $payment->rental_id) }}" class="hover:underline">{{ $payment->invoice_number }}</a>
                            </td>
                            <td class="p-2 align-middle whitespace-nowrap">{{ $payment->customer_name }}</td>
                            <td class="p-2 align-middle whitespace-nowrap">{{ $payment->method->label() }}</td>
                            <td @class(['max-w-56 truncate p-2 align-middle text-muted-foreground'])>
                                {{ $payment->isRefund() ? 'Pengembalian dana' : '' }}{{ $payment->isRefund() && $payment->note ? ' · ' : '' }}{{ $payment->note }}
                            </td>
                            <td @class(['p-2 pr-4 text-right align-middle font-medium whitespace-nowrap tabular-nums', 'text-violet-600 dark:text-violet-400' => $payment->isRefund()])>{{ RentalBill::rupiah($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state>Tidak ada pembayaran di periode ini.</x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>

        {{-- Mobile list --}}
        <x-card class="lg:hidden">
            @forelse ($payments as $payment)
                <a href="{{ route('rentals.show', $payment->rental_id) }}" wire:key="payment-item-{{ $payment->id }}" class="flex items-center gap-3 border-b border-border px-4 py-3 transition-colors last:border-b-0 hover:bg-muted/50">
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $payment->customer_name }}</div>
                        <div class="truncate text-xs tabular-nums text-muted-foreground">
                            {{ $payment->paid_at->translatedFormat('j M') }} · {{ $payment->isRefund() ? 'Pengembalian dana' : $payment->method->label() }} · {{ $payment->invoice_number }}
                        </div>
                    </div>
                    <span @class(['text-sm font-medium tabular-nums', 'text-violet-600 dark:text-violet-400' => $payment->isRefund()])>{{ RentalBill::rupiah($payment->amount) }}</span>
                </a>
            @empty
                <x-empty-state>Tidak ada pembayaran di periode ini.</x-empty-state>
            @endforelse
        </x-card>

        {{ $payments->links() }}
    </div>
</div>
