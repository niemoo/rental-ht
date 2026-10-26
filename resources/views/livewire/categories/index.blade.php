<div class="vstack gap-4">
    <x-page-header title="Kategori" subtitle="Jenis barang yang disewakan, misalnya HT, mic, atau TOA.">
        <x-slot:action>
            @can(\App\Enums\Permission::CreateCategories)
                <x-button variant="success" :href="route('categories.create')"><x-icon name="plus" />Tambah kategori</x-button>
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

    <div class="card mb-0">
        <div class="card-body border-bottom border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="max-width: 28rem">
                    <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari kategori…" />
                </div>
                <x-sort-select
                    class="d-lg-none"
                    :options="['name' => 'Nama', 'rate' => 'Tarif/hari', 'units' => 'Jumlah unit', 'rented' => 'Disewa', 'available' => 'Tersedia']"
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
                            <x-sort-header column="rate" :sort-by="$sortBy" :sort-direction="$sortDirection">Tarif/hari</x-sort-header>
                            <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Jumlah unit</x-sort-header>
                            <x-sort-header column="rented" :sort-by="$sortBy" :sort-direction="$sortDirection">Disewa</x-sort-header>
                            <x-sort-header column="available" :sort-by="$sortBy" :sort-direction="$sortDirection">Tersedia</x-sort-header>
                            @if ($hasActions)
                                <th class="text-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            @php($availableShare = $category->units_count > 0 ? (int) round($category->available_count / $category->units_count * 100) : 0)
                            <tr wire:key="category-row-{{ $category->id }}">
                                <td class="fw-medium">{{ $category->name }}</td>
                                <td class="tabular-nums">{{ \App\Support\RentalBill::rupiah($category->daily_rate) }}</td>
                                <td class="tabular-nums">{{ Number::format($category->units_count) }}</td>
                                <td class="tabular-nums">{{ Number::format($category->out_now_count) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="tabular-nums" style="min-width: 1.75rem">{{ Number::format($category->available_count) }}</span>
                                        <div class="progress progress-sm flex-grow-1" style="max-width: 8rem" role="img" aria-label="{{ $availableShare }}% unit tersedia">
                                            <div class="progress-bar bg-success" style="width: {{ $availableShare }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                @if ($hasActions)
                                    <td class="text-end">
                                        <x-categories.row-actions :category="$category" :can-view-units="$canViewUnits" :can-edit="$canEdit" :can-delete="$canDelete" />
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $hasActions ? 6 : 5 }}"><x-empty-state>Tidak ada kategori yang cocok.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @forelse ($categories as $category)
                <li wire:key="category-item-{{ $category->id }}" class="list-group-item d-flex align-items-center gap-3 py-3">
                    <div class="flex-grow-1" style="min-width: 0">
                        <div class="fw-medium text-truncate">{{ $category->name }}</div>
                        <div class="text-muted fs-12 tabular-nums">
                            {{ \App\Support\RentalBill::rupiah($category->daily_rate) }}/hari · {{ Number::format($category->units_count) }} unit · {{ Number::format($category->available_count) }} tersedia
                        </div>
                    </div>
                    @if ($hasActions)
                        <x-categories.row-actions :category="$category" :can-view-units="$canViewUnits" :can-edit="$canEdit" :can-delete="$canDelete" />
                    @endif
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada kategori yang cocok.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($categories->total() > 0)
            <div class="card-footer">{{ $categories->links() }}</div>
        @endif
    </div>
</div>
