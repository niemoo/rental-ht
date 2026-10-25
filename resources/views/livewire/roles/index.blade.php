@use('App\Enums\Permission')
@use('App\Enums\Role')

<div class="flex flex-col gap-4">
    <x-page-header title="Roles" subtitle="Atur apa saja yang boleh dilakukan setiap role.">
        <x-slot:action>
            <x-button wire:click="create"><x-icon name="plus" />Buat role</x-button>
        </x-slot:action>
    </x-page-header>

    @if ($notice)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-2.5 text-sm shadow-xs">
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" class="text-muted-foreground hover:text-foreground" aria-label="Tutup">
                <x-icon name="x" />
            </button>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->roles as $role)
            @php
                $builtIn = Role::tryFrom($role->name);
                $granted = $role->permissions->pluck('name');
            @endphp
            <section wire:key="role-{{ $role->id }}" class="flex flex-col gap-3 rounded-2xl border border-border bg-card p-4 shadow-xs">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="truncate text-base font-semibold">{{ Role::labelFor($role->name) }}</h2>
                            @if ($builtIn)
                                <span class="rounded-full bg-muted px-1.5 py-0.5 text-[11px] font-medium text-muted-foreground">Bawaan</span>
                            @endif
                        </div>
                        <p class="text-xs tabular-nums text-muted-foreground">
                            {{ $role->users_count }} pengguna · {{ $builtIn?->hasLockedPermissions() ? 'semua izin' : $granted->count().' dari '.count(Permission::cases()).' izin' }}
                        </p>
                    </div>
                    <div class="flex flex-none items-center gap-1">
                        @if ($builtIn?->hasLockedPermissions())
                            <span class="text-xs text-muted-foreground">Tidak bisa diubah</span>
                        @endif
                        @can('update', $role)
                            <x-button variant="ghost" size="icon" wire:click="edit({{ $role->id }})" aria-label="Ubah {{ Role::labelFor($role->name) }}" title="Ubah">
                                <x-icon name="pencil" />
                            </x-button>
                        @endcan
                        @can('delete', $role)
                            <div x-data="{ open: false, loading: false }">
                                <x-button variant="ghost" size="icon" class="text-red-600 hover:text-red-700 dark:text-red-400" x-on:click="open = true" aria-label="Hapus {{ $role->name }}" title="Hapus">
                                    <x-icon name="trash" />
                                </x-button>
                                <x-modal title="Hapus role">
                                    <p class="text-sm text-muted-foreground">Hapus role <strong class="text-foreground">{{ $role->name }}</strong>? Role yang masih dipakai pengguna tidak bisa dihapus.</p>
                                    <x-slot:footer>
                                        <x-button variant="ghost" x-on:click="open = false" x-bind:disabled="loading">Batal</x-button>
                                        <x-button
                                            class="bg-red-600 text-white hover:bg-red-700"
                                            x-on:click="loading = true; $wire.delete({{ $role->id }}).then(() => { loading = false; open = false })"
                                            x-bind:disabled="loading"
                                        >Hapus role</x-button>
                                    </x-slot:footer>
                                </x-modal>
                            </div>
                        @endcan
                    </div>
                </div>

                <div class="h-px bg-border"></div>

                <ul class="flex flex-col gap-1.5 text-sm">
                    @foreach (Permission::cases() as $permission)
                        @php($has = $granted->contains($permission->value))
                        <li @class(['flex items-center gap-2', 'text-muted-foreground/60 line-through decoration-muted-foreground/40' => ! $has])>
                            <span @class([
                                'grid size-4 flex-none place-items-center rounded-full text-[10px] font-bold',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $has,
                                'bg-muted text-muted-foreground' => ! $has,
                            ])>{{ $has ? '✓' : '–' }}</span>
                            {{ $permission->label() }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <x-modal :title="$editingId ? 'Ubah role' : 'Buat role'" show="showFormModal" size="lg">
        <form id="role-form" wire:submit="save" class="flex flex-col gap-4">
            @if ($this->editingBuiltIn)
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Nama role</span>
                    <p class="text-sm">{{ $this->editingBuiltIn->label() }} <span class="text-xs text-muted-foreground">· role bawaan, nama tidak bisa diubah</span></p>
                </div>
            @else
                <x-field label="Nama role" for="role-name" error="name" hint="Contoh: Kasir, Gudang, Admin.">
                    <x-input id="role-name" wire:model="name" placeholder="Nama role" autocomplete="off" />
                </x-field>
            @endif

            <div class="flex flex-col gap-3">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="text-sm font-medium">Izin</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (Permission::grouped() as $group => $groupPermissions)
                        <fieldset class="rounded-lg border border-border p-3">
                            <legend class="px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">{{ $group }}</legend>
                            <div class="flex flex-col gap-2">
                                @foreach ($groupPermissions as $permission)
                                    <label class="flex cursor-pointer items-center gap-2.5 text-sm has-disabled:cursor-not-allowed has-disabled:opacity-70">
                                        <input
                                            type="checkbox"
                                            wire:model="permissions"
                                            value="{{ $permission->value }}"
                                            class="size-4 rounded border-input accent-primary"
                                        >
                                        {{ $permission->label() }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                @error('permissions.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </form>

        <x-slot:footer>
            <x-button variant="ghost" x-on:click="open = false">Batal</x-button>
            <x-button type="submit" form="role-form" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $editingId ? 'Simpan perubahan' : 'Buat role' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>
