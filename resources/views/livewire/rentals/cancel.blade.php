<div class="vstack gap-4">
    <x-page-header title="Batalkan penyewaan" :subtitle="$rental->customer->name.' · '.$rental->start_date->translatedFormat('j M').' → '.$rental->due_date->translatedFormat('j M Y')">
        <x-slot:action>
            <x-button variant="outline" :href="route('rentals.show', $rental)"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <div class="row">
        <div class="col-xl-6 col-lg-8">
            <form
                x-data="confirmAction(@js([
                    'text' => 'Penyewaan '.$rental->customer->name.' akan dibatalkan dan semua unitnya bisa dipesan lagi. Pembatalan tidak bisa diurungkan.',
                    'confirmLabel' => 'Ya, batalkan',
                    'cancelLabel' => 'Kembali',
                    'danger' => true,
                ]))"
                x-on:submit.prevent="ask('save')"
                class="card mb-0"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail pembatalan</h5>
                </div>

                <div class="card-body vstack gap-3">
                    <div class="alert alert-warning alert-border-left mb-0" role="status">
                        Semua unit di penyewaan ini langsung bisa dipesan lagi. Tagihannya diganti dengan biaya pembatalan di bawah.
                        @if ($bill->paid > 0)
                            Pelanggan sudah membayar <strong class="tabular-nums">{{ \App\Support\RentalBill::rupiah($bill->paid) }}</strong>; kelebihannya bisa dicatat sebagai pengembalian dana setelah ini.
                        @endif
                    </div>

                    <x-field label="Alasan" for="cancel-reason" error="reason">
                        <x-input id="cancel-reason" wire:model="reason" placeholder="cth. acara diundur" autofocus />
                    </x-field>

                    <x-field label="Biaya pembatalan" for="cancel-fee" error="fee" hint="Isi 0 kalau tidak ada. Bila ada DP, biaya ini yang tetap ditagih dan sisanya dikembalikan.">
                        <x-money-input id="cancel-fee" wire:model="fee" />
                    </x-field>
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <x-button variant="ghost" :href="route('rentals.show', $rental)">Kembali</x-button>
                    <x-button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Batalkan penyewaan</span>
                        <span wire:loading wire:target="save">Membatalkan…</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
