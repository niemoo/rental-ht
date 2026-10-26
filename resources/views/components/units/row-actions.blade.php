@props(['unit', 'canEdit', 'canDelete'])

{{-- Three-dot menu for one row on the Units page. $unit is a row array from Units\Index. --}}
@php($isBooked = $unit['current'] !== null || $unit['upcoming_count'] > 0)
<x-row-actions
    :label="'unit '.$unit['code']"
    :edit-href="$canEdit ? route('units.edit', $unit['id']) : null"
    :delete-action="$canDelete ? 'delete('.$unit['id'].')' : null"
    delete-button="Hapus unit"
    :delete-blocked="$isBooked"
    {{ $attributes }}
>
    @if ($isBooked)
        Unit <strong class="text-body">{{ $unit['code'] }}</strong> masih disewa atau sudah dipesan, jadi belum bisa dihapus. Selesaikan penyewaannya dulu.
    @else
        Hapus unit <strong class="text-body">{{ $unit['code'] }}</strong>? Riwayat penyewaan unit ini tetap tersimpan.
    @endif
</x-row-actions>
