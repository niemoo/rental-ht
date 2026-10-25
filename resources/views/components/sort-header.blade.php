@props(['column', 'sortBy', 'sortDirection'])

{{-- A table header that sorts by $column through the SortsTable concern. --}}
@php($isActive = $sortBy === $column)
<th
    @if ($isActive) aria-sort="{{ $sortDirection === 'desc' ? 'descending' : 'ascending' }}" @endif
    {{ $attributes->class(['h-10 px-2 text-left align-middle font-medium whitespace-nowrap first:pl-4']) }}
>
    <button
        type="button"
        wire:click="sort('{{ $column }}')"
        @class([
            '-mx-1 inline-flex items-center gap-1 rounded px-1 py-0.5 transition-colors hover:bg-muted hover:text-foreground',
            'text-foreground' => $isActive,
        ])
    >
        {{ $slot }}
        @if ($isActive)
            <x-icon :name="$sortDirection === 'desc' ? 'arrow-down' : 'arrow-up'" class="size-3.5" />
        @else
            <x-icon name="chevrons-up-down" class="size-3.5 text-muted-foreground/60" />
        @endif
    </button>
</th>
