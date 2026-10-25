<div class="flex flex-col gap-4">
    <x-page-header title="Riwayat">
        <x-slot:subtitle><span class="tabular-nums">{{ $total }}</span> penyewaan</x-slot:subtitle>
    </x-page-header>

    @php($canEdit = auth()->user()->can(\App\Enums\Permission::EditRentals))

    <div class="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
        <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari pelanggan, acara, atau unit…" />
        {{-- On desktop these filters live in the Status and Pembayaran column headers. --}}
        <x-column-filter
            as="button"
            class="lg:hidden"
            label="Status"
            model="statuses"
            :options="$statusFilter['options']"
            :counts="$statusFilter['counts']"
            :selected="$statuses"
        />
        <x-column-filter
            as="button"
            class="lg:hidden"
            label="Pembayaran"
            model="paymentStatuses"
            :options="$paymentFilter['options']"
            :counts="$paymentFilter['counts']"
            :selected="$paymentStatuses"
        />
        <x-sort-select
            class="lg:hidden"
            :options="['start' => 'Mulai', 'due' => 'Kembali', 'customer' => 'Pelanggan', 'event' => 'Acara', 'units' => 'Jumlah unit', 'status' => 'Status', 'payment' => 'Sisa tagihan']"
            :sort-by="$sortBy"
            :sort-direction="$sortDirection"
        />
    </div>

    {{-- Desktop table --}}
    <x-card class="hidden lg:block">
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                    <x-sort-header column="event" :sort-by="$sortBy" :sort-direction="$sortDirection">Acara</x-sort-header>
                    <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                    <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Mulai</x-sort-header>
                    <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Kembali</x-sort-header>
                    <x-column-filter
                        label="Status"
                        model="statuses"
                        :options="$statusFilter['options']"
                        :counts="$statusFilter['counts']"
                        :selected="$statuses"
                        column="status"
                        :sort-by="$sortBy"
                        :sort-direction="$sortDirection"
                    />
                    <x-column-filter
                        label="Pembayaran"
                        model="paymentStatuses"
                        :options="$paymentFilter['options']"
                        :counts="$paymentFilter['counts']"
                        :selected="$paymentStatuses"
                        column="payment"
                        :sort-by="$sortBy"
                        :sort-direction="$sortDirection"
                    />
                    <th class="h-10 w-px px-2 pr-4 text-right align-middle font-medium whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                @forelse ($rentals as $rental)
                    @php($timing = $rental->timing())
                    <tr wire:key="history-row-{{ $rental->id }}" class="border-b transition-colors hover:bg-muted/50">
                        <td class="p-2 pl-4 align-middle whitespace-nowrap">
                            <a href="{{ route('rentals.show', $rental) }}" class="font-medium hover:underline">{{ $rental->customer->name }}</a>
                        </td>
                        <td @class(['max-w-56 truncate p-2 align-middle', 'text-muted-foreground' => ! $rental->event_name])>{{ $rental->event_name ?? '—' }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->items_count }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->start_date->translatedFormat('j M Y') }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->due_date->translatedFormat('j M Y') }}</td>
                        <td class="p-2 align-middle whitespace-nowrap"><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                        <td class="p-2 align-middle whitespace-nowrap">
                            @php($paymentStatus = \App\Enums\PaymentStatus::for($rental->total_amount, $rental->paid_amount))
                            <x-payment-badge :status="$paymentStatus" />
                            @if ($paymentStatus === \App\Enums\PaymentStatus::Partial || $paymentStatus === \App\Enums\PaymentStatus::Unpaid)
                                <span class="ml-1 text-xs tabular-nums text-muted-foreground">sisa {{ \App\Support\RentalBill::rupiah($rental->total_amount - $rental->paid_amount) }}</span>
                            @endif
                        </td>
                        <td class="p-2 pr-4 text-right align-middle">
                            <x-rentals.row-actions :rental="$rental" :can-edit="$canEdit" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state>Tidak ada penyewaan yang cocok.</x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    {{-- Mobile list --}}
    <x-card class="lg:hidden">
        @forelse ($rentals as $rental)
            @php($timing = $rental->timing())
            <div wire:key="history-item-{{ $rental->id }}" class="flex items-center gap-3 border-b border-border px-4 py-3 last:border-b-0">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('rentals.show', $rental) }}" class="block truncate text-sm font-medium hover:underline">{{ $rental->customer->name }}</a>
                    <div class="truncate text-xs text-muted-foreground">{{ $rental->event_name ?? $rental->items_count.' unit' }}</div>
                    <div class="text-xs tabular-nums text-muted-foreground">
                        {{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }}
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                    <x-payment-badge :status="\App\Enums\PaymentStatus::for($rental->total_amount, $rental->paid_amount)" />
                </div>
                <x-rentals.row-actions :rental="$rental" :can-edit="$canEdit" class="-mr-1.5" />
            </div>
        @empty
            <x-empty-state>Tidak ada penyewaan yang cocok.</x-empty-state>
        @endforelse
    </x-card>

    {{ $rentals->links() }}
</div>
