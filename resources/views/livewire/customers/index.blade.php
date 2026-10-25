<div class="flex flex-col gap-4">
    <x-page-header title="Pelanggan">
        <x-slot:subtitle>
            <span class="tabular-nums">{{ $totalCount }}</span> pelanggan · <span class="tabular-nums">{{ $withActiveCount }}</span> dengan penyewaan aktif
        </x-slot:subtitle>
        <x-slot:action>
            @can(\App\Enums\Permission::CreateCustomers)
                <x-button :href="route('customers.create')"><x-icon name="plus" />Tambah pelanggan</x-button>
            @endcan
        </x-slot:action>
    </x-page-header>

    <x-notice :message="$notice" />

    @php
        $canEdit = auth()->user()->can(\App\Enums\Permission::EditCustomers);
        $canDelete = auth()->user()->can(\App\Enums\Permission::DeleteCustomers);
    @endphp

    <div class="flex flex-wrap items-center gap-2.5">
        <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari nama atau nomor…" />
        <x-sort-select
            class="lg:hidden"
            :options="['name' => 'Nama', 'phone' => 'Nomor HP', 'active' => 'Penyewaan aktif', 'units' => 'Unit disewa']"
            :sort-by="$sortBy"
            :sort-direction="$sortDirection"
        />
    </div>

    {{-- Desktop table --}}
    <x-card class="hidden lg:block">
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <x-sort-header column="name" :sort-by="$sortBy" :sort-direction="$sortDirection">Nama</x-sort-header>
                    <x-sort-header column="phone" :sort-by="$sortBy" :sort-direction="$sortDirection">Nomor HP</x-sort-header>
                    <x-sort-header column="active" :sort-by="$sortBy" :sort-direction="$sortDirection">Penyewaan aktif</x-sort-header>
                    <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit disewa</x-sort-header>
                    <th class="h-10 w-px px-2 pr-4 text-right align-middle font-medium whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                @forelse ($customers as $customer)
                    <tr wire:key="customer-row-{{ $customer->id }}" class="border-b transition-colors hover:bg-muted/50">
                        <td class="p-2 pl-4 align-middle whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <span class="grid size-8 flex-none place-items-center rounded-full bg-accent text-xs font-semibold text-primary">{{ $customer->initials() }}</span>
                                <a href="{{ route('customers.show', $customer) }}" class="font-medium hover:underline">{{ $customer->name }}</a>
                            </div>
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums text-muted-foreground">{{ $customer->phone }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{!! $customer->active_rentals_count ?: '<span class="text-muted-foreground">—</span>' !!}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{!! $customer->units_out_count ?: '<span class="text-muted-foreground">—</span>' !!}</td>
                        <td class="p-2 pr-4 text-right align-middle">
                            <x-customers.row-actions :customer="$customer" :can-edit="$canEdit" :can-delete="$canDelete" with-detail />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state>Tidak ada pelanggan yang cocok.</x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    {{-- Mobile list --}}
    <x-card class="lg:hidden">
        @forelse ($customers as $customer)
            <div wire:key="customer-item-{{ $customer->id }}" class="flex items-center gap-3 border-b border-border px-3.5 py-3 last:border-b-0">
                <span class="grid size-8 flex-none place-items-center rounded-full bg-accent text-xs font-semibold text-primary">{{ $customer->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('customers.show', $customer) }}" class="block truncate text-sm font-medium hover:underline">{{ $customer->name }}</a>
                    <div class="text-xs tabular-nums text-muted-foreground">{{ $customer->phone }}</div>
                </div>
                @if ($customer->units_out_count)
                    <span class="flex-none rounded-full border border-border bg-muted/60 px-2.5 py-0.5 text-xs font-medium tabular-nums text-muted-foreground">{{ $customer->units_out_count }} disewa</span>
                @else
                    <span class="flex-none text-xs text-muted-foreground">Tidak aktif</span>
                @endif
                <x-customers.row-actions :customer="$customer" :can-edit="$canEdit" :can-delete="$canDelete" with-detail class="-mr-1.5" />
            </div>
        @empty
            <x-empty-state>Tidak ada pelanggan yang cocok.</x-empty-state>
        @endforelse
    </x-card>

    {{ $customers->links() }}
</div>
