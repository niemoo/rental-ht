@use('App\Enums\Permission')
@use('App\Enums\Role')

<div class="vstack gap-4">
    <x-page-header title="Roles" subtitle="Atur apa saja yang boleh dilakukan setiap role.">
        <x-slot:action>
            <x-button variant="success" wire:click="create"><x-icon name="plus" />Buat role</x-button>
        </x-slot:action>
    </x-page-header>

    <x-notice :message="$notice" />

    <div class="row g-3">
        @foreach ($this->roles as $role)
            @php
                $builtIn = Role::tryFrom($role->name);
                $granted = $role->permissions->pluck('name');
                $roleLabel = Role::labelFor($role->name);
            @endphp
            <div class="col-md-6 col-xl-4" wire:key="role-{{ $role->id }}">
                <section class="card mb-0 h-100">
                    <div class="card-header d-flex align-items-start gap-2">
                        <div class="flex-grow-1" style="min-width: 0">
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="card-title mb-0 text-truncate">{{ $roleLabel }}</h5>
                                @if ($builtIn)
                                    <span class="badge bg-light text-muted">Bawaan</span>
                                @endif
                            </div>
                            <p class="text-muted fs-12 tabular-nums mb-0 mt-1">
                                {{ Number::format($role->users_count) }} pengguna · {{ $builtIn?->hasLockedPermissions() ? 'semua izin' : Number::format($granted->count()).' dari '.Number::format(count(Permission::cases())).' izin' }}
                            </p>
                        </div>
                        <div class="d-flex flex-shrink-0 align-items-center gap-1">
                            @if ($builtIn?->hasLockedPermissions())
                                <span class="text-muted fs-12">Tidak bisa diubah</span>
                            @endif
                            @can('update', $role)
                                <x-button variant="ghost" size="icon" wire:click="edit({{ $role->id }})" aria-label="Ubah {{ $roleLabel }}" title="Ubah">
                                    <x-icon name="pencil" />
                                </x-button>
                            @endcan
                            @can('delete', $role)
                                <button
                                    type="button"
                                    class="btn btn-ghost-danger btn-icon btn-sm"
                                    x-data
                                    x-on:click="$confirm(@js($role->users_count > 0
                                        ? ['blocked' => true, 'title' => 'Belum bisa dihapus', 'html' => 'Role <strong class="text-body">'.e($role->name).'</strong> masih dipakai oleh '.Number::format($role->users_count).' pengguna. Pindahkan mereka ke role lain dulu.']
                                        : ['html' => 'Role <strong class="text-body">'.e($role->name).'</strong> akan dihapus.', 'confirmLabel' => 'Ya, hapus role', 'danger' => true]
                                    )).then((ok) => ok && $wire.delete({{ $role->id }}))"
                                    aria-label="Hapus {{ $role->name }}"
                                    title="Hapus"
                                >
                                    <x-icon name="trash" />
                                </button>
                            @endcan
                        </div>
                    </div>

                    <ul class="list-unstyled vstack gap-2 card-body mb-0">
                        @foreach (Permission::cases() as $permission)
                            @php
                                $has = $granted->contains($permission->value);
                            @endphp
                            <li @class(['d-flex align-items-center gap-2', 'text-muted text-decoration-line-through opacity-75' => ! $has])>
                                <span @class(['avatar-title rounded-circle flex-shrink-0 fs-11', 'bg-success-subtle text-success' => $has, 'bg-light text-muted' => ! $has]) style="width: 1.125rem; height: 1.125rem">
                                    <x-icon :name="$has ? 'check' : 'x'" />
                                </span>
                                {{ $permission->label() }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>
        @endforeach
    </div>

    <x-modal :title="$editingId ? 'Ubah role' : 'Buat role'" show="showFormModal" size="lg">
        <form
            id="role-form"
            x-data="confirmAction(@js([
                'text' => 'Izin role ini berlaku untuk semua pengguna yang memakainya, mulai saat disimpan.',
                'confirmLabel' => 'Ya, simpan',
            ]))"
            x-on:submit.prevent="ask('save')"
            class="vstack gap-3"
        >
            @if ($this->editingBuiltIn)
                <div>
                    <span class="form-label d-block">Nama role</span>
                    <p class="mb-0">{{ $this->editingBuiltIn->label() }} <span class="text-muted fs-12">· role bawaan, nama tidak bisa diubah</span></p>
                </div>
            @else
                <x-field label="Nama role" for="role-name" error="name" hint="Contoh: Kasir, Gudang, Admin.">
                    <x-input id="role-name" wire:model="name" placeholder="Nama role" autocomplete="off" />
                </x-field>
            @endif

            <div>
                <span class="form-label d-block">Izin</span>
                <div class="row g-3">
                    @foreach (Permission::grouped() as $group => $groupPermissions)
                        <div class="col-sm-6" wire:key="permission-group-{{ $loop->index }}">
                            <fieldset class="border rounded p-3 h-100">
                                <legend class="float-none w-auto px-1 mb-2 fs-11 fw-semibold text-uppercase text-muted">{{ $group }}</legend>
                                <div class="vstack gap-2">
                                    @foreach ($groupPermissions as $permission)
                                        <div class="form-check" wire:key="permission-{{ $permission->value }}">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                id="permission-{{ $permission->value }}"
                                                wire:model="permissions"
                                                value="{{ $permission->value }}"
                                            >
                                            <label class="form-check-label" for="permission-{{ $permission->value }}">{{ $permission->label() }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>
                    @endforeach
                </div>
                @error('permissions.*') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
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
