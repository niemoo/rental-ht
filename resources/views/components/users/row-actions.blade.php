@props(['user'])

{{-- Edit and delete buttons for one row on the Users page; empty slots keep columns aligned. --}}
<div {{ $attributes->class(['flex flex-none items-center justify-end gap-1']) }}>
    @can('update', $user)
        <x-button variant="ghost" size="icon" :href="route('users.edit', $user)" aria-label="Ubah {{ $user->name }}" title="Ubah">
            <x-icon name="pencil" />
        </x-button>
    @else
        <span class="size-8" aria-hidden="true"></span>
    @endcan

    @can('delete', $user)
        <div x-data="{ open: false, loading: false }">
            <x-button variant="ghost" size="icon" class="text-red-600 hover:text-red-700 dark:text-red-400" x-on:click="open = true" aria-label="Hapus {{ $user->name }}" title="Hapus">
                <x-icon name="trash" />
            </x-button>
            <x-modal title="Hapus pengguna">
                <p class="text-sm text-muted-foreground">Hapus akun <strong class="text-foreground">{{ $user->name }}</strong>? Mereka tidak bisa masuk lagi. Riwayat penyewaan yang pernah mereka buat tetap ada.</p>
                <x-slot:footer>
                    <x-button variant="ghost" x-on:click="open = false" x-bind:disabled="loading">Batal</x-button>
                    <x-button
                        class="bg-red-600 text-white hover:bg-red-700"
                        x-on:click="loading = true; $wire.delete({{ $user->id }}).then(() => { loading = false; open = false })"
                        x-bind:disabled="loading"
                    >Hapus pengguna</x-button>
                </x-slot:footer>
            </x-modal>
        </div>
    @else
        <span class="size-8" aria-hidden="true"></span>
    @endcan
</div>
