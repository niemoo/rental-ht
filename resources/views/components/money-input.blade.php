{{--
    A whole-rupiah amount with an "Rp" prefix, shown with thousand dots while typing (25.000). The binding
    (wire:model…) sits on a hidden field that only ever holds the plain number (25000), so the component's
    validation and maths never see the dots. id, placeholder and the like go to the visible field.
    The wrapper is wire:ignore, so it carries its own wire:key: when Livewire swaps it for another field in the
    same spot (Rupiah ↔ percent discount), it is replaced instead of keeping its old "Rp" markup.
--}}
@php
    $visibleAttributes = ['id', 'placeholder', 'autofocus', 'aria-label', 'disabled', 'required'];
    $key = 'money-input-'.($attributes->get('id') ?: $attributes->wire('model')->value());
    $placeholder = $attributes->get('placeholder');
    if (is_string($placeholder) && ctype_digit($placeholder)) {
        $placeholder = number_format((int) $placeholder, 0, ',', '.');
    }
@endphp

<div class="input-group" wire:ignore wire:key="{{ $key }}" x-data="moneyInput">
    <span class="input-group-text">Rp</span>
    <input
        type="text"
        inputmode="numeric"
        autocomplete="off"
        {{ $attributes->only($visibleAttributes)->except('placeholder')->merge(['placeholder' => $placeholder])->class(['form-control tabular-nums']) }}
        x-bind:value="display"
        x-on:input="type($event)"
    >
    <input type="hidden" x-ref="field" {{ $attributes->except([...$visibleAttributes, 'class']) }}>
</div>
