@props(['rental', 'canEdit'])

{{-- Three-dot menu for one row on the Riwayat page. A completed rental can't be changed, so it only gets "Detail". --}}
<x-row-actions
    :label="'penyewaan '.$rental->customer->name"
    :detail-href="route('rentals.show', $rental)"
    :edit-href="$canEdit && ! $rental->isCompleted() ? route('rentals.edit', $rental) : null"
    {{ $attributes }}
/>
