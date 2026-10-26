<div class="vstack gap-4">
    @php
        $canEdit = auth()->user()->can(\App\Enums\Permission::EditCustomers);
        $canDelete = auth()->user()->can(\App\Enums\Permission::DeleteCustomers);
        $canViewRentals = auth()->user()->can(\App\Enums\Permission::ViewRentals);
    @endphp

    <x-page-header title="Detail pelanggan" :subtitle="$customer->name">
        <x-slot:action>
            <x-button variant="outline" :href="route('customers.index')"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <x-notice :message="$notice" />

    <div class="row g-4">
        {{-- Profile --}}
        <div class="col-xxl-3 col-lg-4">
            <div class="card mb-0">
                <div class="card-body p-4 text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-semibold fs-24">{{ $customer->initials() }}</span>
                    </div>
                    <h5 class="mb-1 text-break">{{ $customer->name }}</h5>
                    <p class="text-muted mb-3 tabular-nums">{{ $customer->phone }}</p>
                    <x-customers.row-actions :customer="$customer" :can-edit="$canEdit" :can-delete="$canDelete" delete-action="delete()" inline />
                </div>
                <div class="card-body border-top border-top-dashed p-4">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr><td class="text-muted ps-0">Total penyewaan</td><td class="text-end pe-0 fw-medium tabular-nums">{{ Number::format($customer->rentals_count) }}</td></tr>
                            <tr><td class="text-muted ps-0">Penyewaan aktif</td><td class="text-end pe-0 fw-medium tabular-nums">{{ Number::format($customer->active_rentals_count) }}</td></tr>
                            <tr><td class="text-muted ps-0">Unit disewa</td><td class="text-end pe-0 fw-medium tabular-nums">{{ Number::format($customer->units_out_count) }}</td></tr>
                            <tr><td class="text-muted ps-0">Ditambahkan oleh</td><td class="text-end pe-0">{{ $customer->creator?->name ?? '—' }}</td></tr>
                            <tr><td class="text-muted ps-0">Ditambahkan pada</td><td class="text-end pe-0 tabular-nums">{{ $customer->created_at->translatedFormat('j M Y') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Rental history --}}
        <div class="col-xxl-9 col-lg-8">
            <div class="card mb-0">
                <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <h4 class="card-title mb-0 flex-grow-1">
                        Riwayat pemesanan <span class="text-muted fw-normal fs-13 tabular-nums">· {{ Number::format($rentals->total()) }}</span>
                    </h4>
                    <x-sort-select
                        class="d-lg-none"
                        :options="['start' => 'Mulai', 'due' => 'Selesai', 'event' => 'Acara', 'units' => 'Jumlah unit']"
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
                                    <x-sort-header column="event" :sort-by="$sortBy" :sort-direction="$sortDirection">Acara</x-sort-header>
                                    <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                                    <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Mulai</x-sort-header>
                                    <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Selesai</x-sort-header>
                                    <th>Status</th>
                                    @if ($canViewRentals)
                                        <th><span class="visually-hidden">Detail</span></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rentals as $rental)
                                    @php($timing = $rental->timing())
                                    <tr wire:key="customer-rental-row-{{ $rental->id }}">
                                        <td @class(['text-truncate', 'text-muted' => ! $rental->event_name]) style="max-width: 18rem">
                                            <x-link-if :href="route('rentals.show', $rental)" :when="$canViewRentals" class="fw-medium" link-class="link-body-emphasis">{{ $rental->event_name ?? '—' }}</x-link-if>
                                        </td>
                                        <td class="tabular-nums">{{ Number::format($rental->items_count) }}</td>
                                        <td class="tabular-nums">{{ $rental->start_date->translatedFormat('j M Y') }}</td>
                                        <td class="tabular-nums">{{ $rental->due_date->translatedFormat('j M Y') }}</td>
                                        <td><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                                        @if ($canViewRentals)
                                            <td class="text-end">
                                                <a href="{{ route('rentals.show', $rental) }}" class="btn btn-ghost-secondary btn-icon btn-sm" aria-label="Detail penyewaan {{ $rental->start_date->translatedFormat('j M Y') }}" title="Detail">
                                                    <x-icon name="chevron-right" class="fs-18" />
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $canViewRentals ? 6 : 5 }}"><x-empty-state>Belum pernah menyewa.</x-empty-state></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile list --}}
                <ul class="list-group list-group-flush d-lg-none">
                    @forelse ($rentals as $rental)
                        @php($timing = $rental->timing())
                        <li class="list-group-item p-0" wire:key="customer-rental-item-{{ $rental->id }}">
                            <x-link-if
                                :href="route('rentals.show', $rental)"
                                :when="$canViewRentals"
                                as="div"
                                class="d-flex align-items-center gap-3 px-3 py-3 text-reset"
                                link-class="list-group-item-action"
                            >
                                <div class="flex-grow-1" style="min-width: 0">
                                    <div class="fw-medium text-truncate">{{ $rental->event_name ?? Number::format($rental->items_count).' unit' }}</div>
                                    <div class="text-muted fs-12 tabular-nums">
                                        {{ $rental->start_date->translatedFormat('j M Y') }} → {{ $rental->due_date->translatedFormat('j M Y') }}
                                    </div>
                                </div>
                                <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                                @if ($canViewRentals)
                                    <x-icon name="chevron-right" class="text-muted fs-18" />
                                @endif
                            </x-link-if>
                        </li>
                    @empty
                        <li class="list-group-item"><x-empty-state>Belum pernah menyewa.</x-empty-state></li>
                    @endforelse
                </ul>

                @if ($rentals->total() > 0)
                    <div class="card-footer">{{ $rentals->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
