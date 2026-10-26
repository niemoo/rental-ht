<div class="vstack gap-4">
    @php($backHref = $customer ? route('customers.show', $customer) : route('customers.index'))

    <x-page-header :title="$customer ? 'Ubah pelanggan' : 'Tambah pelanggan'" :subtitle="$customer ? 'Ganti nama atau nomor HP pelanggan ini.' : 'Catat pelanggan baru supaya bisa dipilih saat membuat penyewaan.'">
        <x-slot:action>
            <x-button variant="outline" :href="$backHref"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <div class="row">
        <div class="col-xl-6 col-lg-8">
            <form
                x-data="confirmAction(@js([
                    'text' => $customer ? 'Perubahan pada pelanggan '.$customer->name.' akan disimpan.' : 'Pelanggan baru akan ditambahkan.',
                    'confirmLabel' => $customer ? 'Ya, simpan perubahan' : 'Ya, simpan pelanggan',
                ]))"
                x-on:submit.prevent="ask('save')"
                class="card mb-0"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail pelanggan</h5>
                </div>

                <div class="card-body vstack gap-3">
                    <x-field label="Nama" for="customer-name" error="name">
                        <x-input id="customer-name" wire:model="name" placeholder="Nama lengkap" autofocus />
                    </x-field>
                    <x-field label="Nomor HP" for="customer-phone" error="phone">
                        <x-input id="customer-phone" wire:model="phone" placeholder="08xx-xxxx-xxxx" inputmode="tel" />
                    </x-field>
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <x-button variant="ghost" :href="$backHref">Batal</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $customer ? 'Simpan perubahan' : 'Simpan pelanggan' }}</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
