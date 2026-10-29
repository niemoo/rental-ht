<div class="vstack gap-4">
    <x-page-header title="Riwayat">
        <x-slot:subtitle><span class="tabular-nums">{{ Number::format($total) }}</span> penyewaan</x-slot:subtitle>
        <x-slot:action>
            @can(\App\Enums\Permission::CreateRentals)
                <x-button variant="success" :href="route('rentals.create')"><x-icon name="plus" />Penyewaan baru</x-button>
            @endcan
        </x-slot:action>
    </x-page-header>

    @php
        $canEdit = auth()->user()->can(\App\Enums\Permission::EditRentals);
        // Status tabs are a shortcut for the Status filter: one status, or none for "Semua".
        $statusTabs = ['' => 'Semua'] + $statusFilter['options'];
        $statusTabCounts = ['' => array_sum($statusFilter['counts'])] + $statusFilter['counts'];
    @endphp

    <div class="card mb-0">
        <div class="card-body border-bottom border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="max-width: 28rem">
                    <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari pelanggan, acara, atau unit…" />
                </div>
                {{-- On desktop these filters live in the Status and Pembayaran column headers. --}}
                <x-column-filter
                    as="button"
                    class="d-lg-none"
                    label="Status"
                    model="statuses"
                    :options="$statusFilter['options']"
                    :counts="$statusFilter['counts']"
                    :selected="$statuses"
                />
                <x-column-filter
                    as="button"
                    class="d-lg-none"
                    label="Pembayaran"
                    model="paymentStatuses"
                    :options="$paymentFilter['options']"
                    :counts="$paymentFilter['counts']"
                    :selected="$paymentStatuses"
                />
                <x-sort-select
                    class="d-lg-none"
                    :options="['start' => 'Mulai', 'due' => 'Selesai', 'customer' => 'Pelanggan', 'event' => 'Acara', 'units' => 'Jumlah unit', 'status' => 'Status', 'payment' => 'Sisa tagihan']"
                    :sort-by="$sortBy"
                    :sort-direction="$sortDirection"
                />
            </div>
        </div>

        <div class="card-body pb-0">
            <ul class="nav nav-tabs nav-tabs-custom nav-success flex-nowrap overflow-x-auto mb-0" role="tablist" aria-label="Status penyewaan">
                @foreach ($statusTabs as $status => $label)
                    @php
                        $isActive = $status === '' ? $statuses === [] : $statuses === [$status];
                    @endphp
                    <li class="nav-item" wire:key="status-tab-{{ $status ?: 'all' }}">
                        <button
                            type="button"
                            role="tab"
                            @class(['nav-link py-3 text-nowrap', 'active' => $isActive])
                            aria-selected="{{ $isActive ? 'true' : 'false' }}"
                            wire:click="$set('statuses', {{ $status === '' ? '[]' : "['".$status."']" }})"
                        >
                            {{ $label }}
                            <span @class(['badge align-middle ms-1 tabular-nums', 'bg-success-subtle text-success' => $isActive, 'bg-light text-muted' => ! $isActive])>{{ Number::format($statusTabCounts[$status] ?? 0) }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Desktop table --}}
        <div class="card-body d-none d-lg-block">
            <div class="table-responsive table-card">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                            <x-sort-header column="event" :sort-by="$sortBy" :sort-direction="$sortDirection">Acara</x-sort-header>
                            <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                            <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Mulai</x-sort-header>
                            <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Selesai</x-sort-header>
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
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rentals as $rental)
                            @php
                                $timing = $rental->timing();
                                $paymentStatus = \App\Enums\PaymentStatus::for($rental->total_amount, $rental->paid_amount);
                            @endphp
                            <tr wire:key="history-row-{{ $rental->id }}" @class(['row-overdue' => $timing->status === \App\Enums\RentalStatus::Overdue])>
                                <td>
                                    <a href="{{ route('rentals.show', $rental) }}" class="fw-medium link-primary">{{ $rental->customer->name }}</a>
                                </td>
                                <td @class(['text-truncate', 'text-muted' => ! $rental->event_name]) style="max-width: 14rem">{{ $rental->event_name ?? '—' }}</td>
                                <td class="tabular-nums">{{ Number::format($rental->items_count) }}</td>
                                <td class="tabular-nums">{{ $rental->start_date->translatedFormat('j M Y') }}</td>
                                <td class="tabular-nums">{{ $rental->due_date->translatedFormat('j M Y') }}</td>
                                <td><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                                <td>
                                    <x-payment-badge :status="$paymentStatus" />
                                    @if ($paymentStatus === \App\Enums\PaymentStatus::Partial || $paymentStatus === \App\Enums\PaymentStatus::Unpaid)
                                        <span class="ms-1 fs-12 text-muted tabular-nums">sisa {{ \App\Support\RentalBill::rupiah($rental->total_amount - $rental->paid_amount) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-rentals.row-actions :rental="$rental" :can-edit="$canEdit" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-empty-state>Tidak ada penyewaan yang cocok.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none mt-3">
            @forelse ($rentals as $rental)
                @php
                    $timing = $rental->timing();
                @endphp
                <li wire:key="history-item-{{ $rental->id }}" @class(['list-group-item d-flex align-items-center gap-3 py-3', 'list-overdue' => $timing->status === \App\Enums\RentalStatus::Overdue])>
                    <div class="flex-grow-1" style="min-width: 0">
                        <a href="{{ route('rentals.show', $rental) }}" class="d-block fw-medium text-truncate link-body-emphasis">{{ $rental->customer->name }}</a>
                        <div class="text-muted fs-12 text-truncate">{{ $rental->event_name ?? Number::format($rental->items_count).' unit' }}</div>
                        <div class="text-muted fs-12 tabular-nums">
                            {{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }}
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                        <x-payment-badge :status="\App\Enums\PaymentStatus::for($rental->total_amount, $rental->paid_amount)" />
                    </div>
                    <x-rentals.row-actions :rental="$rental" :can-edit="$canEdit" />
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada penyewaan yang cocok.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($rentals->total() > 0)
            <div class="card-footer">{{ $rentals->links() }}</div>
        @endif
    </div>
</div>
