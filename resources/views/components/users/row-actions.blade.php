@props(['user'])

{{-- Edit and delete buttons for one row on the Users page; empty slots keep columns aligned. --}}
<div {{ $attributes->class(['d-flex flex-shrink-0 align-items-center justify-content-end gap-1']) }}>
    @can('update', $user)
        <x-button variant="ghost" size="icon" :href="route('users.edit', $user)" aria-label="Ubah {{ $user->name }}" title="Ubah">
            <x-icon name="pencil" />
        </x-button>
    @else
        <span class="btn btn-icon btn-sm invisible" aria-hidden="true"></span>
    @endcan

    @can('delete', $user)
        <button
            type="button"
            class="btn btn-ghost-danger btn-icon btn-sm"
            x-data
            x-on:click="$confirm(@js([
                'html' => 'Hapus akun <strong class="text-body">'.e($user->name).'</strong>? Mereka tidak bisa masuk lagi. Riwayat penyewaan yang pernah mereka buat tetap ada.',
                'confirmLabel' => 'Ya, hapus pengguna',
                'danger' => true,
            ])).then((ok) => ok && $wire.delete({{ $user->id }}))"
            aria-label="Hapus {{ $user->name }}"
            title="Hapus"
        >
            <x-icon name="trash" />
        </button>
    @else
        <span class="btn btn-icon btn-sm invisible" aria-hidden="true"></span>
    @endcan
</div>
