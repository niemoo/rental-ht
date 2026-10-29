<div class="vstack gap-4">
    <x-page-header title="Pelanggan">
        <x-slot:subtitle>
            <span class="tabular-nums">{{ Number::format($totalCount) }}</span> pelanggan · <span class="tabular-nums">{{ Number::format($withActiveCount) }}</span> dengan penyewaan aktif
        </x-slot:subtitle>
        <x-slot:action>
            @can(\App\Enums\Permission::CreateCustomers)
                <x-button variant="success" :href="route('customers.create')"><x-icon name="plus" />Tambah pelanggan</x-button>
            @endcan
        </x-slot:action>
    </x-page-header>

    <x-notice :message="$notice" />

    @php
        $canEdit = auth()->user()->can(\App\Enums\Permission::EditCustomers);
        $canDelete = auth()->user()->can(\App\Enums\Permission::DeleteCustomers);
    @endphp

    <div class="card mb-0">
        <div class="card-body border-bottom border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="max-width: 28rem">
                    <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari nama atau nomor…" />
                </div>
                <x-sort-select
                    class="d-lg-none"
                    :options="['name' => 'Nama', 'phone' => 'Nomor HP', 'active' => 'Penyewaan aktif', 'units' => 'Unit disewa']"
                    :sort-by="$sortBy"
                    :sort-direction="$sortDirection"
                />
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="card-body d-none d-lg-block">
            <div class="table-responsive table-card">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <x-sort-header column="name" :sort-by="$sortBy" :sort-direction="$sortDirection">Nama</x-sort-header>
                            <x-sort-header column="phone" :sort-by="$sortBy" :sort-direction="$sortDirection">Nomor HP</x-sort-header>
                            <x-sort-header column="active" :sort-by="$sortBy" :sort-direction="$sortDirection">Penyewaan aktif</x-sort-header>
                            <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit disewa</x-sort-header>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr wire:key="customer-row-{{ $customer->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs flex-shrink-0">
                                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-semibold fs-11">{{ $customer->initials() }}</span>
                                        </div>
                                        <a href="{{ route('customers.show', $customer) }}" class="fw-medium link-body-emphasis">{{ $customer->name }}</a>
                                    </div>
                                </td>
                                <td class="text-muted tabular-nums">{{ $customer->phone }}</td>
                                <td class="tabular-nums">
                                    @if ($customer->active_rentals_count)
                                        {{ Number::format($customer->active_rentals_count) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="tabular-nums">
                                    @if ($customer->units_out_count)
                                        <span class="badge bg-info-subtle text-info fs-11">{{ Number::format($customer->units_out_count) }} disewa</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-customers.row-actions :customer="$customer" :can-edit="$canEdit" :can-delete="$canDelete" with-detail />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state>Tidak ada pelanggan yang cocok.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @forelse ($customers as $customer)
                <li wire:key="customer-item-{{ $customer->id }}" class="list-group-item d-flex align-items-center gap-3 py-3">
                    <div class="avatar-xs flex-shrink-0">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-semibold fs-11">{{ $customer->initials() }}</span>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0">
                        <a href="{{ route('customers.show', $customer) }}" class="d-block fw-medium text-truncate link-body-emphasis">{{ $customer->name }}</a>
                        <div class="text-muted fs-12 tabular-nums">{{ $customer->phone }}</div>
                    </div>
                    @if ($customer->units_out_count)
                        <span class="badge bg-info-subtle text-info fs-11 flex-shrink-0">{{ Number::format($customer->units_out_count) }} disewa</span>
                    @else
                        <span class="text-muted fs-12 flex-shrink-0">Tidak aktif</span>
                    @endif
                    <x-customers.row-actions :customer="$customer" :can-edit="$canEdit" :can-delete="$canDelete" with-detail />
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada pelanggan yang cocok.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($customers->total() > 0)
            <div class="card-footer">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
