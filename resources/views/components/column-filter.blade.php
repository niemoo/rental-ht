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
    {{ $attributes->class(['h-10 px-2 text-left align-middle font-medium whitespace-nowrap first:pl-4']) }}
>
@endif
    <div x-data="{ popupOpen: false }" x-on:keydown.escape.window="popupOpen = false" @class(['inline-flex', $attributes->get('class') => $as !== 'th'])>
        <button
            type="button"
            x-ref="trigger"
            x-on:click="popupOpen = ! popupOpen"
            aria-haspopup="dialog"
            aria-controls="{{ $popupId }}"
            x-bind:aria-expanded="popupOpen"
            @class([
                'inline-flex items-center gap-1 transition-colors',
                '-mx-1 rounded px-1 py-0.5 hover:bg-muted hover:text-foreground' => $as === 'th',
                'h-8 rounded-lg border border-input bg-white px-2.5 text-sm hover:bg-muted dark:bg-input/30' => $as !== 'th',
                'text-foreground' => $isSorted || $isFiltered,
            ])
        >
            {{ $label }}
            @if ($isFiltered)
                <span class="rounded-full bg-primary px-1.5 text-[11px] leading-4 font-semibold text-primary-foreground tabular-nums" aria-label="{{ count($selected) }} filter aktif">{{ count($selected) }}</span>
            @endif
            @if ($isSorted)
                <x-icon :name="$sortDirection === 'desc' ? 'arrow-down' : 'arrow-up'" class="size-3.5" />
            @endif
            <x-icon name="filter" @class(['size-3.5', 'text-primary' => $isFiltered, 'text-muted-foreground/60' => ! $isFiltered]) />
        </button>

        <template x-teleport="body">
            <div
                id="{{ $popupId }}"
                x-show="popupOpen"
                x-cloak
                x-anchor.bottom-start.offset.4="$refs.trigger"
                x-on:click.outside="if (! $refs.trigger.contains($event.target)) popupOpen = false"
                x-transition.opacity.duration.100ms
                role="dialog"
                aria-label="Urutkan dan filter {{ $label }}"
                class="z-50 w-56 rounded-lg border border-border bg-card p-1 text-sm font-normal shadow-md"
            >
                @if ($column)
                    <div class="px-2.5 pt-1.5 pb-1 text-[11px] font-semibold tracking-wider text-muted-foreground">URUTKAN</div>
                    @foreach (['asc' => ['Naik', 'arrow-up'], 'desc' => ['Turun', 'arrow-down']] as $direction => [$directionLabel, $icon])
                        @php($isActive = $isSorted && $sortDirection === $direction)
                        <button
                            type="button"
                            wire:click="sortTo('{{ $column }}', '{{ $direction }}')"
                            x-on:click="popupOpen = false"
                            @class(['flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left hover:bg-muted', 'font-medium text-primary' => $isActive])
                        >
                            <x-icon :name="$icon" class="size-4 text-muted-foreground" />
                            <span class="flex-1">{{ $directionLabel }}</span>
                            @if ($isActive)
                                <x-icon name="check" class="size-4" />
                            @endif
                        </button>
                    @endforeach
                    <div class="my-1 h-px bg-border"></div>
                @endif

                <div class="flex items-center justify-between px-2.5 pt-1.5 pb-1">
                    <span class="text-[11px] font-semibold tracking-wider text-muted-foreground">FILTER</span>
                    @if ($isFiltered)
                        <button type="button" wire:click="$set('{{ $model }}', [])" class="text-xs font-medium text-primary hover:underline">Hapus filter</button>
                    @endif
                </div>
                @foreach ($options as $value => $optionLabel)
                    <label wire:key="{{ $popupId }}-{{ $value }}" class="flex cursor-pointer items-center gap-2 rounded-md px-2.5 py-1.5 hover:bg-muted">
                        <input type="checkbox" wire:model.live="{{ $model }}" value="{{ $value }}" class="size-4 rounded border-input accent-primary">
                        <span class="flex-1">{{ $optionLabel }}</span>
                        @if ($counts !== null)
                            <span class="text-xs tabular-nums text-muted-foreground">{{ $counts[$value] ?? 0 }}</span>
                        @endif
                    </label>
                @endforeach
            </div>
        </template>
    </div>
@if ($as === 'th')
</th>
@endif
