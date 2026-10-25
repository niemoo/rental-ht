<div class="flex flex-col gap-4">
    <x-page-header title="Unit">
        <x-slot:subtitle>
            <span class="tabular-nums">{{ $summary['all'] }}</span> total · <span class="tabular-nums">{{ $summary['available'] }}</span> tersedia hari ini
        </x-slot:subtitle>
        <x-slot:action>
            @can(\App\Enums\Permission::CreateUnits)
                <x-button :href="route('units.create', $singleCategoryId ? ['category' => $singleCategoryId] : [])"><x-icon name="plus" />Tambah unit</x-button>
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
            'dueStates' => ['label' => 'Jatuh tempo', 'column' => 'due'],
        ];
    @endphp

    <div class="flex flex-wrap items-center gap-2.5">
        <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari kode, kategori, atau penyewa…" />
        {{-- On desktop these filters live in the column headers. --}}
        @foreach ($filterColumns as $model => ['label' => $label])
            <x-column-filter
                as="button"
                class="lg:hidden"
                :label="$label"
                :model="$model"
                :options="$filters[$model]['options']"
                :counts="$filters[$model]['counts']"
                :selected="$this->{$model}"
            />
        @endforeach
        <x-sort-select
            class="lg:hidden"
            :options="['code' => 'Kode', 'category' => 'Kategori', 'condition' => 'Kondisi', 'status' => 'Status', 'renter' => 'Penyewa', 'due' => 'Jatuh tempo']"
            :sort-by="$sortBy"
            :sort-direction="$sortDirection"
        />
    </div>

    {{-- Desktop table --}}
    <x-card class="hidden lg:block">
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead>
                <tr class="border-b">
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
                        label="Jatuh tempo"
                        model="dueStates"
                        :options="$filters['dueStates']['options']"
                        :counts="$filters['dueStates']['counts']"
                        :selected="$dueStates"
                        column="due"
                        :sort-by="$sortBy"
                        :sort-direction="$sortDirection"
                    />
                    @if ($hasActions)
                        <th class="h-10 w-px px-2 pr-4 text-right align-middle font-medium whitespace-nowrap">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                @forelse ($units as $unit)
                    @php($timing = $unit['current'] ? \App\Support\RentalTiming::for($unit['current']->rental->start_date, $unit['current']->rental->due_date) : null)
                    <tr wire:key="unit-row-{{ $unit['code'] }}" @class([
                        'border-b transition-colors hover:bg-muted/50',
                        '[&>td:first-child]:shadow-[inset_3px_0_0_var(--color-red-600)]' => $timing?->status === \App\Enums\RentalStatus::Overdue,
                    ])>
                        <td class="p-2 pl-4 align-middle whitespace-nowrap"><x-unit-chip :code="$unit['code']" /></td>
                        <td class="p-2 align-middle whitespace-nowrap text-muted-foreground">{{ $unit['category'] }}</td>
                        <td class="p-2 align-middle whitespace-nowrap"><x-condition-badge :condition="$unit['condition']" /></td>
                        <td class="p-2 align-middle whitespace-nowrap"><x-units.status-badge :unit="$unit" /></td>
                        <td @class(['p-2 align-middle whitespace-nowrap', 'text-muted-foreground' => ! $unit['current']])>
                            {{ $unit['current']?->rental->customer->name ?? '—' }}
                            @if ($unit['upcoming_count'])
                                <span class="ml-2 text-xs text-muted-foreground">· {{ $unit['upcoming_count'] }} mendatang</span>
                            @endif
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap">
                            @if ($unit['current'])
                                <span class="tabular-nums">
                                    <span class="font-medium">{{ $unit['current']->rental->due_date->translatedFormat('j M') }}</span>
                                    <span class="ml-1 text-xs {{ $timing->status->textClass() }}">{{ $timing->label }}</span>
                                </span>
                            @else
                                <span class="text-muted-foreground">—</span>
                            @endif
                        </td>
                        @if ($hasActions)
                            <td class="p-2 pr-4 text-right align-middle">
                                <x-units.row-actions :unit="$unit" :can-edit="$canEdit" :can-delete="$canDelete" />
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $hasActions ? 7 : 6 }}"><x-empty-state>Tidak ada unit yang cocok.</x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    {{-- Mobile list --}}
    <x-card class="lg:hidden">
        @forelse ($units as $unit)
            @php($timing = $unit['current'] ? \App\Support\RentalTiming::for($unit['current']->rental->start_date, $unit['current']->rental->due_date) : null)
            <div wire:key="unit-item-{{ $unit['code'] }}" @class([
                'flex items-center gap-3 border-b border-border px-3.5 py-3 last:border-b-0',
                'shadow-[inset_3px_0_0_var(--color-red-600)]' => $timing?->status === \App\Enums\RentalStatus::Overdue,
            ])>
                <div class="flex flex-none flex-col items-start gap-0.5">
                    <x-unit-chip :code="$unit['code']" />
                    <span class="text-[11px] text-muted-foreground">{{ $unit['category'] }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div @class(['truncate text-sm font-medium', 'text-muted-foreground' => ! $unit['current']])>
                        {{ $unit['current']?->rental->customer->name ?? 'Tidak disewa' }}
                    </div>
                    @unless ($unit['condition']->isUsable())
                        <x-condition-badge :condition="$unit['condition']" class="mt-0.5" />
                    @endunless
                    @if ($timing)
                        <div class="text-xs tabular-nums {{ $timing->status->textClass() }}">{{ $timing->label }}</div>
                    @elseif ($unit['upcoming_count'])
                        <div class="text-xs text-muted-foreground">{{ $unit['upcoming_count'] }} mendatang</div>
                    @endif
                </div>
                <x-units.status-badge :unit="$unit" />
                @if ($hasActions)
                    <x-units.row-actions :unit="$unit" :can-edit="$canEdit" :can-delete="$canDelete" class="-mr-1.5" />
                @endif
            </div>
        @empty
            <x-empty-state>Tidak ada unit yang cocok.</x-empty-state>
        @endforelse
    </x-card>

    {{ $units->links() }}
</div>
