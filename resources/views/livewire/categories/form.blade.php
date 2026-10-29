<div class="vstack gap-4">
    <x-page-header :title="$category ? 'Ubah kategori' : 'Tambah kategori'" :subtitle="$category ? 'Ganti nama atau tarif harian kategori ini.' : 'Daftarkan jenis barang baru beserta tarif hariannya.'">
        <x-slot:action>
            <x-button variant="outline" :href="route('categories.index')"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <div class="row">
        <div class="col-xl-6 col-lg-8">
            <form
                x-data="confirmAction(@js([
                    'text' => $category ? 'Perubahan pada kategori '.$category->name.' akan disimpan. Tarif baru dipakai untuk pemesanan berikutnya dan penyewaan yang diubah setelah ini.' : 'Kategori baru akan ditambahkan.',
                    'confirmLabel' => $category ? 'Ya, simpan perubahan' : 'Ya, simpan kategori',
                ]))"
                x-on:submit.prevent="ask('save')"
                class="card mb-0"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail kategori</h5>
                </div>

                <div class="card-body vstack gap-3">
                    <x-field label="Nama" for="category-name" error="name" hint="Jenis barang yang disewakan, misalnya HT, Mic, atau TOA.">
                        <x-input id="category-name" wire:model="name" placeholder="cth. HT, Mic, TOA" autofocus />
                    </x-field>

                    <x-field label="Tarif per hari" for="category-daily-rate" error="dailyRate" hint="Harga sewa satu unit per hari. Tarif baru dipakai untuk pemesanan berikutnya dan saat penyewaan yang belum selesai diubah.">
                        <x-money-input id="category-daily-rate" wire:model="dailyRate" placeholder="25000" />
                    </x-field>
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <x-button variant="ghost" :href="route('categories.index')">Batal</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $category ? 'Simpan perubahan' : 'Simpan kategori' }}</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
