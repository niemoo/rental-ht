@props(['options', 'sortBy', 'sortDirection'])

{{-- Mobile counterpart of <x-sort-header>: $options is [column => label]; each column is offered ascending and descending. --}}
@php
    $choices = [];
    foreach ($options as $column => $label) {
        foreach (['asc' => '↑', 'desc' => '↓'] as $direction => $arrow) {
            $choices["{$column}:{$direction}"] = "{$label} {$arrow}";
        }
    }
@endphp

<div {{ $attributes->class(['d-flex align-items-center gap-2 text-muted fs-12']) }}>
    <span>Urutkan</span>
    <div style="width: 10.5rem">
        <x-select
            size="sm"
            :options="$choices"
            :selected="$sortBy.':'.$sortDirection"
            aria-label="Urutkan"
            x-on:change="$wire.sortTo(...$event.target.value.split(':'))"
        />
    </div>
</div>
