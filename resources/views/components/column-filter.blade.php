@props([
    'label',
    'model',
    'options',
    'selected' => [],
    'counts' => null,
    'column' => null,
    'sortBy' => null,
    'sortDirection' => null,
    'as' => 'th',
])

{{--
    A column title that opens a popup to sort by the column and filter it, instead of sorting on click.

    $model is an array Livewire property (e.g. "conditions") bound to the checkboxes; nothing ticked means
    no filter. $options is [value => label]; optional $counts is [value => int], shown beside each option.
    Pass $column (a SortsTable key) to also offer sorting.
    as="th" renders a table header; as="button" renders a standalone button for the mobile toolbar.
    The popup is teleported to <body> so the table's scroll container can't clip it.
--}}
@php
    $selected = array_values(array_intersect(array_map('strval', array_keys($options)), array_map('strval', (array) $selected)));
    $isFiltered = $selected !== [];
    $isSorted = $column !== null && $sortBy === $column;
    $popupId = 'column-filter-'.$model.'-'.$as;
@endphp

@if ($as === 'th')
<th
    @if ($isSorted) aria-sort="{{ $sortDirection === 'desc' ? 'descending' : 'ascending' }}" @endif
    {{ $attributes->class(['text-nowrap']) }}
>
@endif
    <div x-data="{ popupOpen: false }" x-on:keydown.escape.window="popupOpen = false" @class(['d-inline-flex', $attributes->get('class') => $as !== 'th'])>
        <button
            type="button"
            x-ref="trigger"
            x-on:click="popupOpen = ! popupOpen"
            aria-haspopup="dialog"
            aria-controls="{{ $popupId }}"
            x-bind:aria-expanded="popupOpen"
            @class([
                'sort-button' => $as === 'th',
                'is-active' => $as === 'th' && ($isSorted || $isFiltered),
                'btn btn-soft-secondary btn-sm d-inline-flex align-items-center gap-1' => $as !== 'th',
            ])
        >
            {{ $label }}
            @if ($isFiltered)
                <span class="badge bg-primary rounded-pill tabular-nums" aria-label="{{ count($selected) }} filter aktif">{{ count($selected) }}</span>
            @endif
            @if ($isSorted)
                <x-icon :name="$sortDirection === 'desc' ? 'arrow-down' : 'arrow-up'" class="text-primary" />
            @endif
            <x-icon name="filter" @class(['text-primary' => $isFiltered, 'opacity-50' => ! $isFiltered]) />
        </button>

        <template x-teleport="body">
            <div
                id="{{ $popupId }}"
                x-show="popupOpen"
                x-cloak
                x-anchor.bottom-start.offset.4="$refs.trigger"
                x-on:click.outside="if (! $refs.trigger.contains($event.target)) popupOpen = false"
                role="dialog"
                aria-label="Urutkan dan filter {{ $label }}"
                class="dropdown-menu show p-1 fw-normal text-body"
                style="width: 15rem"
            >
                @if ($column)
                    <h6 class="dropdown-header text-uppercase fs-11">Urutkan</h6>
                    @foreach (['asc' => ['Naik', 'arrow-up'], 'desc' => ['Turun', 'arrow-down']] as $direction => [$directionLabel, $icon])
                        @php($isActive = $isSorted && $sortDirection === $direction)
                        <button
                            type="button"
                            wire:click="sortTo('{{ $column }}', '{{ $direction }}')"
                            x-on:click="popupOpen = false"
                            @class(['dropdown-item d-flex align-items-center gap-2 rounded', 'active' => $isActive])
                        >
                            <x-icon :name="$icon" class="text-muted" />
                            <span class="flex-grow-1">{{ $directionLabel }}</span>
                            @if ($isActive)
                                <x-icon name="check" />
                            @endif
                        </button>
                    @endforeach
                    <div class="dropdown-divider"></div>
                @endif

                <div class="d-flex align-items-center justify-content-between px-3 pt-2 pb-1">
                    <span class="text-muted text-uppercase fw-semibold fs-11">Filter</span>
                    @if ($isFiltered)
                        <button type="button" wire:click="$set('{{ $model }}', [])" class="btn btn-link btn-sm p-0 fs-12">Hapus filter</button>
                    @endif
                </div>
                @foreach ($options as $value => $optionLabel)
                    <label wire:key="{{ $popupId }}-{{ $value }}" class="dropdown-item d-flex align-items-center gap-2 rounded mb-0" style="cursor: pointer">
                        <input type="checkbox" wire:model.live="{{ $model }}" value="{{ $value }}" class="form-check-input m-0">
                        <span class="flex-grow-1">{{ $optionLabel }}</span>
                        @if ($counts !== null)
                            <span class="badge bg-light text-muted tabular-nums">{{ $counts[$value] ?? 0 }}</span>
                        @endif
                    </label>
                @endforeach
            </div>
        </template>
    </div>
@if ($as === 'th')
</th>
@endif
