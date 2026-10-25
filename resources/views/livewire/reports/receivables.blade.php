@php use App\Support\RentalBill; use App\Support\Reports\ReceivablesReport; @endphp
<div class="flex flex-col gap-5">
    <x-reports.header
        active="receivables"
        :report="$report"
        :period-query="\App\Support\ReportPeriod::fromInput($period, $from, $to)->query()"
        :filter-query="$agings === [] ? [] : ['aging' => $agings]"
    />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-5">
        <x-stat-tile label="Total piutang" :value="RentalBill::rupiah($figures['total'])" :sub="$figures['count'].' pemesanan'" />
        <x-stat-tile label="Sudah selesai" :value="RentalBill::rupiah($figures['by_aging']['days_0_7']['total'] + $figures['by_aging']['days_8_30']['total'] + $figures['by_aging']['days_over_30']['total'])" sub="sewa selesai, belum lunas" />
        <x-stat-tile label="Lewat 30 hari" :value="RentalBill::rupiah($figures['by_aging']['days_over_30']['total'])" :sub="$figures['by_aging']['days_over_30']['count'].' pemesanan'" />
        <x-stat-tile label="Lebih bayar" :value="RentalBill::rupiah($figures['overpaid_total'])" :sub="$figures['overpaid_count'].' perlu dikembalikan'" />
    </div>

    {{-- Aging --}}
    <x-chart-card title="Umur piutang" subtitle="Dihitung dari tanggal kembali, atau tanggal batal">
        @php($largest = max(1, ...array_column($figures['by_aging'], 'total')))
        <div class="flex flex-col gap-3 text-sm">
            @foreach (ReceivablesReport::AGINGS as $aging => $label)
                @php($group = $figures['by_aging'][$aging])
                <button
                    type="button"
                    wire:click="$set('agings', {{ in_array($aging, $agings, true) && count($agings) === 1 ? '[]' : "['".$aging."']" }})"
                    wire:key="aging-bar-{{ $aging }}"
                    @class(['grid grid-cols-[9rem_minmax(0,1fr)_auto] items-center gap-3 rounded-md text-left transition-opacity hover:opacity-80', 'opacity-40' => $agings !== [] && ! in_array($aging, $agings, true)])
                >
                    <span class="text-muted-foreground">{{ $label }}</span>
                    <span class="h-2.5 overflow-hidden rounded-full bg-muted">
                        <span @class([
                            'block h-full rounded-full',
                            'bg-slate-400' => $aging === 'upcoming',
                            'bg-primary' => $aging === 'running',
                            'bg-amber-500' => $aging === 'days_0_7',
                            'bg-orange-500' => $aging === 'days_8_30',
                            'bg-red-600' => $aging === 'days_over_30',
                        ]) style="width: {{ round($group['total'] / $largest * 100) }}%"></span>
                    </span>
                    <span class="text-right tabular-nums"><span class="font-medium">{{ RentalBill::rupiah($group['total']) }}</span> <span class="text-xs text-muted-foreground">· {{ $group['count'] }}</span></span>
                </button>
            @endforeach
        </div>
    </x-chart-card>

    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <h2 class="text-base font-semibold">Belum lunas <span class="text-sm font-medium tabular-nums text-muted-foreground">· {{ $rentals->total() }}</span></h2>
            <div class="flex gap-2.5 lg:hidden">
                <x-column-filter as="button" label="Umur" model="agings" :options="$agingFilter['options']" :counts="$agingFilter['counts']" :selected="$agings" />
                <x-sort-select
                    :options="['balance' => 'Sisa', 'invoice' => 'Invoice', 'customer' => 'Pelanggan', 'due' => 'Kembali', 'aging' => 'Umur']"
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
                        <x-sort-header column="invoice" :sort-by="$sortBy" :sort-direction="$sortDirection">Invoice</x-sort-header>
                        <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                        <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Sewa</x-sort-header>
                        <x-column-filter label="Umur" model="agings" :options="$agingFilter['options']" :counts="$agingFilter['counts']" :selected="$agings" column="aging" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                        <x-sort-header column="total" :sort-by="$sortBy" :sort-direction="$sortDirection">Total</x-sort-header>
                        <x-sort-header column="paid" :sort-by="$sortBy" :sort-direction="$sortDirection">Dibayar</x-sort-header>
                        <x-sort-header column="balance" :sort-by="$sortBy" :sort-direction="$sortDirection" class="pr-4">Sisa</x-sort-header>
                    </tr>
                </thead>
                <tbody class="[&_tr:last-child]:border-0">
                    @forelse ($rentals as $rental)
                        @php($days = $report->daysOwed($rental))
                        <tr wire:key="receivable-row-{{ $rental->id }}" class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-2 pl-4 align-middle whitespace-nowrap tabular-nums"><a href="{{ route('rentals.show', $rental) }}" class="hover:underline">{{ $rental->invoice_number }}</a></td>
                            <td class="p-2 align-middle whitespace-nowrap">
                                <div class="font-medium">{{ $rental->customer_name }}</div>
                                <div class="text-xs tabular-nums text-muted-foreground">{{ $rental->customer->phone }}</div>
                            </td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M Y') }}</td>
                            <td class="p-2 align-middle whitespace-nowrap">
                                {{ ReceivablesReport::AGINGS[$rental->aging] }}
                                @if ($days > 0)
                                    <span class="text-xs tabular-nums text-muted-foreground">· {{ $days }} hari</span>
                                @endif
                            </td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ RentalBill::rupiah($rental->total_amount) }}</td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ RentalBill::rupiah($rental->paid_amount) }}</td>
                            <td class="p-2 pr-4 align-middle font-semibold whitespace-nowrap tabular-nums">{{ RentalBill::rupiah((int) $rental->balance) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state>Tidak ada piutang. Semua tagihan sudah lunas.</x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>

        {{-- Mobile list --}}
        <x-card class="lg:hidden">
            @forelse ($rentals as $rental)
                <a href="{{ route('rentals.show', $rental) }}" wire:key="receivable-item-{{ $rental->id }}" class="flex items-center gap-3 border-b border-border px-4 py-3 transition-colors last:border-b-0 hover:bg-muted/50">
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $rental->customer_name }}</div>
                        <div class="truncate text-xs tabular-nums text-muted-foreground">{{ $rental->invoice_number }} · {{ ReceivablesReport::AGINGS[$rental->aging] }}</div>
                    </div>
                    <span class="text-sm font-semibold tabular-nums">{{ RentalBill::rupiah((int) $rental->balance) }}</span>
                </a>
            @empty
                <x-empty-state>Tidak ada piutang. Semua tagihan sudah lunas.</x-empty-state>
            @endforelse
        </x-card>

        {{ $rentals->links() }}
    </div>

    @if ($overpaid->isNotEmpty())
        <div class="flex flex-col gap-3">
            <h2 class="text-base font-semibold">Lebih bayar <span class="text-sm font-medium tabular-nums text-muted-foreground">· perlu dikembalikan</span></h2>
            <x-card>
                @foreach ($overpaid as $rental)
                    <a href="{{ route('rentals.show', $rental) }}" wire:key="overpaid-{{ $rental->id }}" class="flex items-center gap-3 border-b border-border px-4 py-3 transition-colors last:border-b-0 hover:bg-muted/50">
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium">{{ $rental->customer->name }}</div>
                            <div class="text-xs tabular-nums text-muted-foreground">{{ $rental->invoice_number }} · total {{ RentalBill::rupiah($rental->total_amount) }}, dibayar {{ RentalBill::rupiah($rental->paid_amount) }}</div>
                        </div>
                        <span class="text-sm font-semibold text-violet-600 tabular-nums dark:text-violet-400">{{ RentalBill::rupiah((int) $rental->overpaid) }}</span>
                    </a>
                @endforeach
            </x-card>
        </div>
    @endif
</div>
