@props(['label', 'detailHref' => null, 'editHref' => null, 'deleteAction' => null, 'deleteButton' => 'Hapus', 'deleteBlocked' => false, 'inline' => false])

{{--
    Three-dot menu for a table row with "Detail" and "Ubah" (links) and "Hapus" (asks in a SweetAlert2 dialog
    first, then calls the Livewire action, e.g. delete-action="delete(12)"). The slot is the dialog's text;
    with delete-blocked the dialog only explains why and offers no delete button.
    Renders nothing when no action is passed, so callers can pass null for missing permissions.
    The menu is teleported to <body> so the table's scroll container can't clip it.
    inline: the same actions as labelled buttons side by side, for a detail page rather than a table row.
--}}
@if ($detailHref || $editHref || $deleteAction)
    @php
        $dialog = $deleteBlocked
            ? ['blocked' => true, 'title' => 'Belum bisa dihapus', 'html' => trim((string) $slot)]
            : ['title' => 'Apakah Anda yakin?', 'html' => trim((string) $slot), 'confirmLabel' => 'Ya, '.mb_strtolower($deleteButton), 'danger' => true];
    @endphp
    <div
        x-data="{
            menuOpen: false,
            @if ($deleteAction)
                remove() {
                    this.menuOpen = false;
                    this.$confirm(@js($dialog)).then((ok) => ok && this.$wire.{{ $deleteAction }});
                },
            @endif
        }"
        x-on:keydown.escape.window="menuOpen = false"
        {{ $attributes->class(['d-inline-flex', 'flex-wrap justify-content-center gap-2' => $inline]) }}
    >
        @if ($inline)
            @if ($detailHref)
                <a href="{{ $detailHref }}" class="btn btn-soft-secondary btn-sm"><x-icon name="eye" class="align-bottom me-1" /> Detail</a>
            @endif
            @if ($editHref)
                <a href="{{ $editHref }}" class="btn btn-soft-primary btn-sm"><x-icon name="pencil" class="align-bottom me-1" /> Ubah</a>
            @endif
            @if ($deleteAction)
                <button type="button" class="btn btn-soft-danger btn-sm" x-on:click="remove()" aria-label="Hapus {{ $label }}">
                    <x-icon name="trash" class="align-bottom me-1" /> Hapus
                </button>
            @endif
        @else
            <button
                type="button"
                class="btn btn-soft-secondary btn-sm"
                x-ref="trigger"
                x-on:click="menuOpen = ! menuOpen"
                aria-haspopup="menu"
                x-bind:aria-expanded="menuOpen"
                aria-label="Aksi untuk {{ $label }}"
                title="Aksi"
            >
                <x-icon name="ellipsis" class="align-middle" />
            </button>

            <template x-teleport="body">
                <ul
                    x-show="menuOpen"
                    x-cloak
                    x-anchor.bottom-end.offset.4="$refs.trigger"
                    x-on:click.outside="if (! $refs.trigger.contains($event.target)) menuOpen = false"
                    role="menu"
                    class="dropdown-menu show"
                >
                    @if ($detailHref)
                        <li><a href="{{ $detailHref }}" role="menuitem" class="dropdown-item"><x-icon name="eye" class="align-bottom me-2 text-muted" /> Detail</a></li>
                    @endif
                    @if ($editHref)
                        <li><a href="{{ $editHref }}" role="menuitem" class="dropdown-item"><x-icon name="pencil" class="align-bottom me-2 text-muted" /> Ubah</a></li>
                    @endif
                    @if ($deleteAction)
                        @if ($detailHref || $editHref)
                            <li><hr class="dropdown-divider"></li>
                        @endif
                        <li>
                            <button type="button" role="menuitem" x-on:click="remove()" class="dropdown-item text-danger" aria-label="Hapus {{ $label }}">
                                <x-icon name="trash" class="align-bottom me-2" /> Hapus
                            </button>
                        </li>
                    @endif
                </ul>
            </template>
        @endif
    </div>
@endif
