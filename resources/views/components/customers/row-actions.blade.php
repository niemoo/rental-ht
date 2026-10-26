@props(['customer', 'canEdit', 'canDelete', 'withDetail' => false, 'deleteAction' => null, 'inline' => false])

{{--
    Actions for a customer: a three-dot menu on a Customers row (with-detail), or Ubah / Hapus buttons
    (inline) on their detail page, which passes its own delete-action. Expects active_rentals_count on $customer.
--}}
<x-row-actions
    :label="'pelanggan '.$customer->name"
    :detail-href="$withDetail ? route('customers.show', $customer) : null"
    :edit-href="$canEdit ? route('customers.edit', $customer) : null"
    :delete-action="$canDelete ? ($deleteAction ?? 'delete('.$customer->id.')') : null"
    delete-button="Hapus pelanggan"
    :delete-blocked="$customer->active_rentals_count > 0"
    :inline="$inline"
    {{ $attributes }}
>
    @if ($customer->active_rentals_count)
        Pelanggan <strong class="text-body">{{ $customer->name }}</strong> masih punya {{ $customer->active_rentals_count }} penyewaan yang belum selesai, jadi belum bisa dihapus. Selesaikan penyewaannya dulu.
    @else
        Hapus pelanggan <strong class="text-body">{{ $customer->name }}</strong>? Riwayat penyewaannya tetap tersimpan.
    @endif
</x-row-actions>
