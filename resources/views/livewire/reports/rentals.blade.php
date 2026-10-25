@php use App\Support\RentalBill; @endphp
<div class="flex flex-col gap-5">
    <x-reports.header
        active="rentals"
        :report="$report"
        :period-query="$this->reportPeriod->query()"
        :filter-query="$statuses === [] ? [] : ['status' => $statuses]"
        :period="$period"
        :from="$from"
        :to="$to"
    />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-5">
        <x-stat-tile label="Pemesanan" :value="$figures['count']" :sub="$figures['cancelled'] > 0 ? $figures['cancelled'].' dibatalkan ('.$figures['cancelled_pct'].'%)' : 'tidak ada yang batal'" />
        <x-stat-tile label="Omzet" :value="RentalBill::rupiah($figures['omzet'])" sub="sudah atau belum dibayar" />
        <x-stat-tile label="Rata-rata nilai" :value="RentalBill::rupiah($figures['average_value'])" sub="per pemesanan" />
        <x-stat-tile label="Rata-rata durasi" :value="str_replace('.', ',', (string) $figures['average_days']).' hari'" sub="per pemesanan" />
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            <x-chart-card title="Pemesanan & omzet" :subtitle="($this->reportPeriod->groupsByDay() ? 'Per hari' : 'Per bulan').', menurut tanggal mulai sewa'">
                <x-chart type="rentalsAndOmzet" :data="$chart" />
            </x-chart-card>
        </div>
        <x-chart-card title="Potongan & biaya tambahan" subtitle="Pada sewa periode ini">
            <dl class="flex flex-col gap-2.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-muted-foreground">Diskon diberikan</dt>
                    <dd class="font-medium tabular-nums text-red-600 dark:text-red-400">−{{ RentalBill::rupiah($figures['discount']) }}</dd>
                </div>
                <div class="h-px bg-border"></div>
                @foreach ($figures['charges'] as $type => $total)
                    <div class="flex justify-between gap-3" wire:key="charge-type-{{ $type }}">
                        <dt class="text-muted-foreground">{{ \App\Enums\ChargeType::from($type)->label() }}</dt>
                        <dd @class(['tabular-nums', 'font-medium' => $total > 0, 'text-muted-foreground' => $total === 0])>{{ RentalBill::rupiah($total) }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-chart-card>
    </div>

    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <h2 class="text-base font-semibold">Daftar pemesanan <span class="text-sm font-medium tabular-nums text-muted-foreground">· {{ $rentals->total() }}</span></h2>
            <div class="flex gap-2.5 lg:hidden">
                <x-column-filter as="button" label="Status" model="statuses" :options="$statusFilter['options']" :counts="$statusFilter['counts']" :selected="$statuses" />
                <x-sort-select
                    :options="['start' => 'Mulai', 'invoice' => 'Invoice', 'customer' => 'Pelanggan', 'days' => 'Durasi', 'units' => 'Unit', 'status' => 'Status', 'total' => 'Total', 'balance' => 'Sisa']"
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
                        <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Sewa</x-sort-header>
                        <x-sort-header column="days" :sort-by="$sortBy" :sort-direction="$sortDirection">Hari</x-sort-header>
                        <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                        <x-column-filter label="Status" model="statuses" :options="$statusFilter['options']" :counts="$statusFilter['counts']" :selected="$statuses" column="status" :sort-by="$sortBy" :sort-direction="$sortDirection" />
                        <x-sort-header column="total" :sort-by="$sortBy" :sort-direction="$sortDirection">Total</x-sort-header>
                        <x-sort-header column="balance" :sort-by="$sortBy" :sort-direction="$sortDirection" class="pr-4">Sisa</x-sort-header>
                    </tr>
                </thead>
                <tbody class="[&_tr:last-child]:border-0">
                    @forelse ($rentals as $rental)
                        @php($timing = $rental->timing())
                        <tr wire:key="report-rental-row-{{ $rental->id }}" class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-2 pl-4 align-middle whitespace-nowrap tabular-nums"><a href="{{ route('rentals.show', $rental) }}" class="hover:underline">{{ $rental->invoice_number }}</a></td>
                            <td class="p-2 align-middle whitespace-nowrap">
                                <div class="font-medium">{{ $rental->customer_name }}</div>
                                @if ($rental->event_name)
                                    <div class="max-w-56 truncate text-xs text-muted-foreground">{{ $rental->event_name }}</div>
                                @endif
                            </td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M Y') }}</td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->days }}</td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->items_count }}</td>
                            <td class="p-2 align-middle whitespace-nowrap"><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                            <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ RentalBill::rupiah($rental->total_amount) }}</td>
                            <td @class(['p-2 pr-4 align-middle whitespace-nowrap tabular-nums', 'text-muted-foreground' => (int) $rental->balance === 0, 'font-medium' => (int) $rental->balance !== 0])>{{ RentalBill::rupiah((int) $rental->balance) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state>Tidak ada pemesanan di periode ini.</x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>

        {{-- Mobile list --}}
        <x-card class="lg:hidden">
            @forelse ($rentals as $rental)
                @php($timing = $rental->timing())
                <a href="{{ route('rentals.show', $rental) }}" wire:key="report-rental-item-{{ $rental->id }}" class="flex items-center gap-3 border-b border-border px-4 py-3 transition-colors last:border-b-0 hover:bg-muted/50">
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $rental->customer_name }}</div>
                        <div class="truncate text-xs tabular-nums text-muted-foreground">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }} · {{ RentalBill::rupiah($rental->total_amount) }}</div>
                    </div>
                    <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                </a>
            @empty
                <x-empty-state>Tidak ada pemesanan di periode ini.</x-empty-state>
            @endforelse
        </x-card>

        {{ $rentals->links() }}
    </div>
</div>
