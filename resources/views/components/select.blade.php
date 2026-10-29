@props(['options', 'searchable' => false, 'selected' => null, 'size' => null])

{{--
    Velzon select (Choices.js). $options is [value => label]; every other attribute (id, wire:model, x-model,
    x-on:change…) goes to the <select>, which Choices keeps in sync. searchable adds the type-to-filter box for
    long lists; $selected preselects a value when nothing is bound; size="sm" is the compact toolbar size.
    The wrapper is wire:ignore, so it carries its own wire:key: if Livewire swaps it for another field in the
    same spot, it is replaced instead of keeping the old widget.
--}}
@php
    $key = 'select-'.($attributes->get('id') ?: $attributes->wire('model')->value() ?: $attributes->get('x-model') ?: $attributes->get('aria-label'));
@endphp
<div wire:ignore wire:key="{{ $key }}" x-data="choicesSelect(@js(['searchEnabled' => (bool) $searchable]))" @class(['choices-sm' => $size === 'sm'])>
    <select x-ref="select" {{ $attributes->class(['form-select']) }}>
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected($selected !== null && (string) $value === (string) $selected)>{{ $label }}</option>
        @endforeach
    </select>
</div>
