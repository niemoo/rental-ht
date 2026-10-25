@props(['options', 'sortBy', 'sortDirection'])

{{-- Mobile counterpart of <x-sort-header>: $options is [column => label]. --}}
<label {{ $attributes->class(['flex items-center gap-2 text-xs text-muted-foreground']) }}>
    Urutkan
    <select
        x-on:change="$wire.sortTo(...$event.target.value.split(':'))"
        class="h-8 rounded-lg border border-input bg-white px-2 text-sm text-foreground outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
    >
        @foreach ($options as $column => $label)
            @foreach (['asc' => '↑', 'desc' => '↓'] as $direction => $arrow)
                <option value="{{ $column }}:{{ $direction }}" @selected($sortBy === $column && $sortDirection === $direction)>{{ $label }} {{ $arrow }}</option>
            @endforeach
        @endforeach
    </select>
</label>
