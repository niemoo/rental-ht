<div class="flex flex-col gap-4">
    <div>
        <a href="{{ route('categories.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">{{ $category ? 'Ubah kategori' : 'Tambah kategori' }}</h1>
    </div>

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4 rounded-xl border border-border bg-card p-4">
        <x-field label="Nama" for="category-name" error="name" hint="Jenis barang yang disewakan, misalnya HT, Mic, atau TOA.">
            <x-input id="category-name" wire:model="name" placeholder="cth. HT, Mic, TOA" autofocus />
        </x-field>

        <x-field label="Tarif per hari" for="category-daily-rate" error="dailyRate" hint="Harga sewa satu unit per hari. Perubahan tarif hanya berlaku untuk pemesanan berikutnya.">
            <x-money-input id="category-daily-rate" wire:model="dailyRate" placeholder="25000" />
        </x-field>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <x-button variant="ghost" :href="route('categories.index')">Batal</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $category ? 'Simpan perubahan' : 'Simpan kategori' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
