<div class="vstack gap-4">
    <x-page-header :title="$user ? 'Ubah pengguna' : 'Tambah pengguna'" :subtitle="$user ? 'Ganti data, kata sandi, atau role '.$user->name.'.' : 'Buat akun untuk orang yang boleh masuk ke aplikasi.'">
        <x-slot:action>
            <x-button variant="outline" :href="route('users.index')"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <div class="row">
        <div class="col-xl-6 col-lg-8">
            <form
                x-data="confirmAction(@js([
                    'text' => $user ? 'Perubahan pada akun '.$user->name.' akan disimpan.' : 'Akun baru akan dibuat dan langsung bisa dipakai masuk.',
                    'confirmLabel' => $user ? 'Ya, simpan perubahan' : 'Ya, buat akun',
                ]))"
                x-on:submit.prevent="ask('save')"
                class="card mb-0"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail pengguna</h5>
                </div>

                <div class="card-body vstack gap-3">
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
                    <x-field label="Role" for="user-role" error="role" hint="Izin tiap role diatur di halaman Roles.">
                        <x-select id="user-role" wire:model="role" :options="$this->roleOptions" />
                    </x-field>
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <x-button variant="ghost" :href="route('users.index')">Batal</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $user ? 'Simpan perubahan' : 'Simpan pengguna' }}</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
