@props(['column', 'sortBy', 'sortDirection'])

{{-- A table header that sorts by $column through the SortsTable concern. --}}
@php($isActive = $sortBy === $column)
<th
    @if ($isActive) aria-sort="{{ $sortDirection === 'desc' ? 'descending' : 'ascending' }}" @endif
    {{ $attributes->class(['text-nowrap']) }}
>
    <button type="button" wire:click="sort('{{ $column }}')" @class(['sort-button', 'is-active' => $isActive])>
        {{ $slot }}
        @if ($isActive)
            <x-icon :name="$sortDirection === 'desc' ? 'arrow-down' : 'arrow-up'" class="text-primary" />
        @else
            <x-icon name="chevrons-up-down" class="opacity-50" />
        @endif
    </button>
</th>
