@props(['unit'])

{{-- Rental status of a row from Units\Index: out now, free to book, or on the shelf but not usable (see Kondisi). --}}
@if ($unit['current'])
    <x-status-badge status="rented" {{ $attributes }}>Disewa</x-status-badge>
@elseif ($unit['condition']->isUsable())
    <x-status-badge status="available" {{ $attributes }}>Tersedia</x-status-badge>
@else
    <x-status-badge status="unusable" {{ $attributes }}>Tidak tersedia</x-status-badge>
@endif
