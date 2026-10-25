@props(['type', 'data', 'height' => 320])

{{--
    Rendered client-side by resources/js/charts.js. wire:ignore keeps Livewire from clobbering the SVG;
    the data-derived wire:key makes Livewire swap in a fresh chart when the numbers change.
--}}
<div wire:ignore wire:key="chart-{{ $type }}-{{ md5(json_encode($data)) }}" class="w-full min-w-0 overflow-hidden" style="min-height: {{ $height }}px">
    <div x-data="chart(@js($type), @js($data))"></div>
</div>
