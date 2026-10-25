<div class="flex flex-col gap-4">
    <div>
        <a href="{{ route('users.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">{{ $user ? 'Ubah pengguna' : 'Tambah pengguna' }}</h1>
    </div>

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4 rounded-xl border border-border bg-card p-4">
        <x-field label="Nama" for="user-name" error="name">
            <x-input id="user-name" wire:model="name" placeholder="Nama lengkap" autocomplete="off" autofocus />
        </x-field>
        <x-field label="Email" for="user-email" error="email">
            <x-input id="user-email" type="email" wire:model="email" placeholder="nama@contoh.com" autocomplete="off" />
        </x-field>
        <x-field
            label="Kata sandi"
            for="user-password"
            error="password"
            :hint="$user ? 'Kosongkan jika tidak ingin mengubah kata sandi.' : 'Minimal 8 karakter. Berikan ke pengguna secara langsung.'"
        >
            <x-input id="user-password" type="password" wire:model="password" autocomplete="new-password" />
        </x-field>
        <x-field label="Role" for="user-role" error="role">
            <select id="user-role" wire:model="role" class="h-9 w-full rounded-lg border border-input bg-white px-2.5 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30">
                @foreach ($this->roleOptions as $value => $label)
                    <option value="{{ $value }}" wire:key="role-option-{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <x-button variant="ghost" :href="route('users.index')">Batal</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $user ? 'Simpan perubahan' : 'Simpan pengguna' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
