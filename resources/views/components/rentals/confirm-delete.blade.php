@props(['label', 'title', 'action'])

{{--
    Small trash button that asks first, then calls the Livewire action (e.g. action="deletePayment(4)").
    Used for payments and extra charges on a rental's bill. The slot is the confirmation text.
--}}
<div x-data="{ open: false, loading: false }" class="inline-flex">
    <button
        type="button"
        x-on:click="open = true"
        class="grid size-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
        aria-label="Hapus {{ $label }}"
        title="Hapus"
    >
        <x-icon name="trash" class="size-3.5" />
    </button>

    <x-modal :title="$title">
        <p class="text-sm text-muted-foreground">{{ $slot }}</p>
        <x-slot:footer>
            <x-button variant="ghost" x-on:click="open = false" x-bind:disabled="loading">Batal</x-button>
            <x-button
                class="bg-red-600 text-white hover:bg-red-700"
                x-on:click="loading = true; $wire.{{ $action }}.then(() => { loading = false; open = false })"
                x-bind:disabled="loading"
            >Hapus</x-button>
        </x-slot:footer>
    </x-modal>
</div>
