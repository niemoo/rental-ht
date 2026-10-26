@props(['label', 'title', 'action'])

{{--
    Small trash button that asks in a SweetAlert2 dialog first, then calls the Livewire action
    (e.g. action="deletePayment(4)"). Used for payments and extra charges on a rental's bill.
    The slot is the dialog's text; $title ("Hapus pembayaran") names the confirm button.
--}}
<button
    type="button"
    class="btn btn-ghost-danger btn-icon btn-sm"
    x-data
    x-on:click="$confirm(@js([
        'html' => trim((string) $slot),
        'confirmLabel' => 'Ya, '.mb_strtolower($title),
        'danger' => true,
    ])).then((ok) => ok && $wire.{{ $action }})"
    aria-label="Hapus {{ $label }}"
    title="Hapus"
>
    <x-icon name="trash" />
</button>
