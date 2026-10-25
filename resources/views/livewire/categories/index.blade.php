<div class="flex flex-col gap-4">
    <x-page-header title="Kategori" subtitle="Jenis barang yang disewakan, misalnya HT, mic, atau TOA.">
        <x-slot:action>
            @can(\App\Enums\Permission::CreateCategories)
                <x-button :href="route('categories.create')"><x-icon name="plus" />Tambah kategori</x-button>
            @endcan
        </x-slot:action>
    </x-page-header>

    <x-notice :message="$notice" />

    @php
        $canEdit = auth()->user()->can(\App\Enums\Permission::EditCategories);
        $canDelete = auth()->user()->can(\App\Enums\Permission::DeleteCategories);
        $canViewUnits = auth()->user()->can(\App\Enums\Permission::ViewUnits);
        $hasActions = $canViewUnits || $canEdit || $canDelete;
    @endphp

    <div class="flex flex-wrap items-center gap-2.5">
        <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari kategori…" />
        <x-sort-select
            class="lg:hidden"
            :options="['name' => 'Nama', 'rate' => 'Tarif/hari', 'units' => 'Jumlah unit', 'rented' => 'Disewa', 'available' => 'Tersedia']"
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
                    <x-sort-header column="rate" :sort-by="$sortBy" :sort-direction="$sortDirection">Tarif/hari</x-sort-header>
                    <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Jumlah unit</x-sort-header>
                    <x-sort-header column="rented" :sort-by="$sortBy" :sort-direction="$sortDirection">Disewa</x-sort-header>
                    <x-sort-header column="available" :sort-by="$sortBy" :sort-direction="$sortDirection">Tersedia</x-sort-header>
                    @if ($hasActions)
                        <th class="h-10 w-px px-2 pr-4 text-right align-middle font-medium whitespace-nowrap">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                @forelse ($categories as $category)
                    <tr wire:key="category-row-{{ $category->id }}" class="border-b transition-colors hover:bg-muted/50">
                        <td class="p-2 pl-4 align-middle font-medium whitespace-nowrap">{{ $category->name }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ \App\Support\RentalBill::rupiah($category->daily_rate) }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $category->units_count }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $category->out_now_count }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $category->available_count }}</td>
                        @if ($hasActions)
                            <td class="p-2 pr-4 text-right align-middle">
                                <x-categories.row-actions :category="$category" :can-view-units="$canViewUnits" :can-edit="$canEdit" :can-delete="$canDelete" />
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $hasActions ? 6 : 5 }}"><x-empty-state>Tidak ada kategori yang cocok.</x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    {{-- Mobile list --}}
    <x-card class="lg:hidden">
        @forelse ($categories as $category)
            <div wire:key="category-item-{{ $category->id }}" class="flex items-center gap-3 border-b border-border px-3.5 py-3 last:border-b-0">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium">{{ $category->name }}</div>
                    <div class="text-xs tabular-nums text-muted-foreground">
                        {{ \App\Support\RentalBill::rupiah($category->daily_rate) }}/hari · {{ $category->units_count }} unit · {{ $category->available_count }} tersedia
                    </div>
                </div>
                @if ($hasActions)
                    <x-categories.row-actions :category="$category" :can-view-units="$canViewUnits" :can-edit="$canEdit" :can-delete="$canDelete" class="-mr-1.5" />
                @endif
            </div>
        @empty
            <x-empty-state>Tidak ada kategori yang cocok.</x-empty-state>
        @endforelse
    </x-card>

    {{ $categories->links() }}
</div>
