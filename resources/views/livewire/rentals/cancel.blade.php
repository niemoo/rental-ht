<div class="flex flex-col gap-4">
    <div>
        <a href="{{ route('rentals.show', $rental) }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">Batalkan penyewaan</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            {{ $rental->customer->name }} · <span class="tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M Y') }}</span>
        </p>
    </div>

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4 rounded-xl border border-border bg-card p-4">
        <p class="text-sm text-muted-foreground">
            Semua unit di penyewaan ini langsung bisa dipesan lagi. Tagihannya diganti dengan biaya pembatalan di bawah.
            @if ($bill->paid > 0)
                Pelanggan sudah membayar <strong class="text-foreground tabular-nums">{{ \App\Support\RentalBill::rupiah($bill->paid) }}</strong>; kelebihannya bisa dicatat sebagai pengembalian dana setelah ini.
            @endif
        </p>

        <x-field label="Alasan" for="cancel-reason" error="reason">
            <x-input id="cancel-reason" wire:model="reason" placeholder="cth. acara diundur" autofocus />
        </x-field>

        <x-field label="Biaya pembatalan" for="cancel-fee" error="fee" hint="Isi 0 kalau tidak ada. Bila ada DP, biaya ini yang tetap ditagih dan sisanya dikembalikan.">
            <x-money-input id="cancel-fee" wire:model="fee" />
        </x-field>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <x-button variant="ghost" :href="route('rentals.show', $rental)">Kembali</x-button>
            <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Batalkan penyewaan</span>
                <span wire:loading wire:target="save">Membatalkan…</span>
            </x-button>
        </div>
    </form>
</div>
