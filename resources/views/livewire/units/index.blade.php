<div class="vstack gap-4">
    <x-page-header title="Unit">
        <x-slot:subtitle>
            <span class="tabular-nums">{{ Number::format($summary['all']) }}</span> total · <span class="tabular-nums">{{ Number::format($summary['available']) }}</span> tersedia hari ini
        </x-slot:subtitle>
        <x-slot:action>
            @can(\App\Enums\Permission::CreateUnits)
                <x-button variant="success" :href="route('units.create', $singleCategoryId ? ['category' => $singleCategoryId] : [])"><x-icon name="plus" />Tambah unit</x-button>
            @endcan
        </x-slot:action>
    </x-page-header>

    <x-notice :message="$notice" />

    @php
        $canEdit = auth()->user()->can(\App\Enums\Permission::EditUnits);
        $canDelete = auth()->user()->can(\App\Enums\Permission::DeleteUnits);
        $hasActions = $canEdit || $canDelete;
        $filterColumns = [
            'categoryIds' => ['label' => 'Kategori', 'column' => 'category'],
            'conditions' => ['label' => 'Kondisi', 'column' => 'condition'],
            'statuses' => ['label' => 'Status', 'column' => 'status'],
            'dueStates' => ['label' => 'Selesai', 'column' => 'due'],
        ];
    @endphp

    <div class="card mb-0">
        <div class="card-body border-bottom border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="max-width: 28rem">
                    <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari kode, kategori, atau penyewa…" />
                </div>
                {{-- On desktop these filters live in the column headers. --}}
                @foreach ($filterColumns as $model => ['label' => $label])
                    <x-column-filter
                        as="button"
                        class="d-lg-none"
                        :label="$label"
                        :model="$model"
                        :options="$filters[$model]['options']"
                        :counts="$filters[$model]['counts']"
                        :selected="$this->{$model}"
                    />
                @endforeach
                <x-sort-select
                    class="d-lg-none"
                    :options="['code' => 'Kode', 'category' => 'Kategori', 'condition' => 'Kondisi', 'status' => 'Status', 'renter' => 'Penyewa', 'due' => 'Selesai']"
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
                            <x-sort-header column="code" :sort-by="$sortBy" :sort-direction="$sortDirection">Kode</x-sort-header>
                            @foreach (['categoryIds', 'conditions', 'statuses'] as $model)
                                <x-column-filter
                                    :label="$filterColumns[$model]['label']"
                                    :model="$model"
                                    :options="$filters[$model]['options']"
                                    :counts="$filters[$model]['counts']"
                                    :selected="$this->{$model}"
                                    :column="$filterColumns[$model]['column']"
                                    :sort-by="$sortBy"
                                    :sort-direction="$sortDirection"
                                />
                            @endforeach
                            <x-sort-header column="renter" :sort-by="$sortBy" :sort-direction="$sortDirection">Penyewa saat ini</x-sort-header>
                            <x-column-filter
                                label="Selesai"
                                model="dueStates"
                                :options="$filters['dueStates']['options']"
                                :counts="$filters['dueStates']['counts']"
                                :selected="$dueStates"
                                column="due"
                                :sort-by="$sortBy"
                                :sort-direction="$sortDirection"
                            />
                            @if ($hasActions)
                                <th class="text-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($units as $unit)
                            @php($timing = $unit['current'] ? \App\Support\RentalTiming::for($unit['current']->rental->start_date, $unit['current']->rental->due_date) : null)
                            <tr wire:key="unit-row-{{ $unit['code'] }}" @class(['row-overdue' => $timing?->status === \App\Enums\RentalStatus::Overdue])>
                                <td><x-unit-chip :code="$unit['code']" /></td>
                                <td class="text-muted">{{ $unit['category'] }}</td>
                                <td><x-condition-badge :condition="$unit['condition']" /></td>
                                <td><x-units.status-badge :unit="$unit" /></td>
                                <td>
                                    @if ($unit['current'])
                                        {{ $unit['current']->rental->customer->name }}
                                        @if ($unit['upcoming_count'])
                                            <span class="ms-2 fs-12 text-muted">· {{ $unit['upcoming_count'] }} mendatang</span>
                                        @endif
                                    @elseif ($unit['upcoming_count'])
                                        <span class="text-muted">{{ $unit['upcoming_count'] }} mendatang</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="tabular-nums">
                                    @if ($unit['current'])
                                        <span class="fw-medium">{{ $unit['current']->rental->due_date->translatedFormat('j M') }}</span>
                                        <span class="ms-1 fs-12 {{ $timing->status->textClass() }}">{{ $timing->label }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                @if ($hasActions)
                                    <td class="text-end">
                                        <x-units.row-actions :unit="$unit" :can-edit="$canEdit" :can-delete="$canDelete" />
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $hasActions ? 7 : 6 }}"><x-empty-state>Tidak ada unit yang cocok.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @forelse ($units as $unit)
                @php($timing = $unit['current'] ? \App\Support\RentalTiming::for($unit['current']->rental->start_date, $unit['current']->rental->due_date) : null)
                <li wire:key="unit-item-{{ $unit['code'] }}" @class(['list-group-item d-flex align-items-center gap-3 py-3', 'list-overdue' => $timing?->status === \App\Enums\RentalStatus::Overdue])>
                    <div class="d-flex flex-column align-items-start gap-1 flex-shrink-0">
                        <x-unit-chip :code="$unit['code']" />
                        <span class="text-muted fs-11">{{ $unit['category'] }}</span>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0">
                        <div @class(['fw-medium text-truncate', 'text-muted' => ! $unit['current']])>
                            {{ $unit['current']?->rental->customer->name ?? 'Tidak disewa' }}
                        </div>
                        @unless ($unit['condition']->isUsable())
                            <x-condition-badge :condition="$unit['condition']" class="mt-1" />
                        @endunless
                        @if ($timing)
                            <div class="fs-12 tabular-nums {{ $timing->status->textClass() }}">{{ $timing->label }}</div>
                        @elseif ($unit['upcoming_count'])
                            <div class="fs-12 text-muted">{{ $unit['upcoming_count'] }} mendatang</div>
                        @endif
                    </div>
                    <x-units.status-badge :unit="$unit" />
                    @if ($hasActions)
                        <x-units.row-actions :unit="$unit" :can-edit="$canEdit" :can-delete="$canDelete" />
                    @endif
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada unit yang cocok.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($units->total() > 0)
            <div class="card-footer">{{ $units->links() }}</div>
        @endif
    </div>
</div>
