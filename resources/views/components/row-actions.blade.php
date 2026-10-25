@props(['label', 'detailHref' => null, 'editHref' => null, 'deleteAction' => null, 'deleteTitle' => 'Hapus', 'deleteButton' => 'Hapus', 'deleteBlocked' => false])

{{--
    Three-dot menu for a table row with "Detail" and "Ubah" (links) and "Hapus" (asks first, then
    calls the Livewire action, e.g. delete-action="delete(12)"). The slot is the delete confirmation
    text; with delete-blocked the dialog only explains why and offers no delete button.
    Renders nothing when no action is passed, so callers can pass null for missing permissions.
    The menu is teleported to <body> so the table's scroll container can't clip it.
--}}
@if ($detailHref || $editHref || $deleteAction)
    <div x-data="{ menuOpen: false, open: false, loading: false }" x-on:keydown.escape.window="menuOpen = false" {{ $attributes->class(['inline-flex']) }}>
        <x-button
            variant="ghost"
            size="icon"
            x-ref="trigger"
            x-on:click="menuOpen = ! menuOpen"
            aria-haspopup="menu"
            x-bind:aria-expanded="menuOpen"
            aria-label="Aksi untuk {{ $label }}"
            title="Aksi"
        >
            <x-icon name="ellipsis" />
        </x-button>

        <template x-teleport="body">
            <div
                x-show="menuOpen"
                x-cloak
                x-anchor.bottom-end.offset.4="$refs.trigger"
                x-on:click.outside="if (! $refs.trigger.contains($event.target)) menuOpen = false"
                x-transition.opacity.duration.100ms
                role="menu"
                class="z-50 min-w-36 rounded-lg border border-border bg-card p-1 text-sm shadow-md"
            >
                @if ($detailHref)
                    <a href="{{ $detailHref }}" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-foreground hover:bg-muted">
                        <x-icon name="eye" class="size-4 text-muted-foreground" /> Detail
                    </a>
                @endif
                @if ($editHref)
                    <a href="{{ $editHref }}" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-foreground hover:bg-muted">
                        <x-icon name="pencil" class="size-4 text-muted-foreground" /> Ubah
                    </a>
                @endif
                @if ($deleteAction)
                    <button
                        type="button"
                        role="menuitem"
                        x-on:click="menuOpen = false; open = true"
                        class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                    >
                        <x-icon name="trash" class="size-4" /> Hapus
                    </button>
                @endif
            </div>
        </template>

        @if ($deleteAction)
            <x-modal :title="$deleteTitle">
                <p class="text-sm text-muted-foreground">{{ $slot }}</p>
                <x-slot:footer>
                    @if ($deleteBlocked)
                        <x-button variant="outline" x-on:click="open = false">Tutup</x-button>
                    @else
                        <x-button variant="ghost" x-on:click="open = false" x-bind:disabled="loading">Batal</x-button>
                        <x-button
                            class="bg-red-600 text-white hover:bg-red-700"
                            x-on:click="loading = true; $wire.{{ $deleteAction }}.then(() => { loading = false; open = false })"
                            x-bind:disabled="loading"
                        >{{ $deleteButton }}</x-button>
                    @endif
                </x-slot:footer>
            </x-modal>
        @endif
    </div>
@endif
