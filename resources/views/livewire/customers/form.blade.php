<div class="flex flex-col gap-4">
    @php($backHref = $customer ? route('customers.show', $customer) : route('customers.index'))
    <div>
        <a href="{{ $backHref }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">{{ $customer ? 'Ubah pelanggan' : 'Tambah pelanggan' }}</h1>
    </div>

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4 rounded-xl border border-border bg-card p-4">
        <x-field label="Nama" for="customer-name" error="name">
            <x-input id="customer-name" wire:model="name" placeholder="Nama lengkap" autofocus />
        </x-field>
        <x-field label="Nomor HP" for="customer-phone" error="phone">
            <x-input id="customer-phone" wire:model="phone" placeholder="08xx-xxxx-xxxx" inputmode="tel" />
        </x-field>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <x-button variant="ghost" :href="$backHref">Batal</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $customer ? 'Simpan perubahan' : 'Simpan pelanggan' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
