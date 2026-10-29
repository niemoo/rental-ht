@php
    use App\Support\RentalBill;

    $bill = $this->bill;
    $backHref = route('rentals.show', $rental);
@endphp
<div class="vstack gap-4">
    <x-page-header title="Catat pembayaran" :subtitle="$rental->invoice_number.' · '.$rental->customer->name">
        <x-slot:action>
            <x-button variant="outline" :href="$backHref"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <div class="row">
        <div class="col-xl-6 col-lg-8">
            <form
                x-data="confirmAction(@js([
                    'text' => 'Jumlah ini akan dicatat di tagihan '.$rental->invoice_number.' dan sisa tagihannya dihitung ulang.',
                    'confirmLabel' => 'Ya, simpan',
                ]))"
                x-on:submit.prevent="ask('save')"
                class="card mb-0"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail pembayaran</h5>
                </div>

                <div class="card-body border-bottom border-bottom-dashed">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <p class="text-muted fs-13 mb-1">Total</p>
                            <h6 class="mb-0 tabular-nums">{{ RentalBill::rupiah($bill->total) }}</h6>
                        </div>
                        <div class="col-4 border-start border-end">
                            <p class="text-muted fs-13 mb-1">Dibayar</p>
                            <h6 class="mb-0 tabular-nums">{{ RentalBill::rupiah($bill->paid) }}</h6>
                        </div>
                        <div class="col-4">
                            <p class="text-muted fs-13 mb-1">{{ $bill->balance() < 0 ? 'Lebih bayar' : 'Sisa' }}</p>
                            <h6 @class(['mb-0 tabular-nums', 'text-primary' => $bill->balance() < 0, 'text-danger' => $bill->balance() > 0, 'text-success' => $bill->balance() === 0])>{{ RentalBill::rupiah(abs($bill->balance())) }}</h6>
                        </div>
                    </div>
                </div>

                <div class="card-body vstack gap-3">
                    @if ($bill->balance() < 0)
                        <div>
                            <span class="form-label d-block">Jenis</span>
                            <div class="btn-group" role="group" aria-label="Jenis">
                                @foreach (['payment' => 'Pembayaran', 'refund' => 'Pengembalian dana'] as $value => $label)
                                    <input type="radio" class="btn-check" id="kind-{{ $value }}" wire:model.live="kind" value="{{ $value }}" wire:key="kind-{{ $value }}">
                                    <label class="btn btn-outline-primary" for="kind-{{ $value }}" wire:key="kind-label-{{ $value }}">{{ $label }}</label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <x-field :label="$kind === 'refund' ? 'Jumlah dikembalikan' : 'Jumlah dibayar'" for="payment-amount" error="amount">
                                <x-money-input id="payment-amount" wire:model="amount" placeholder="500000" autofocus />
                            </x-field>
                        </div>
                        <div class="col-sm-6">
                            <x-field label="Tanggal" for="payment-date" error="paidAt">
                                <x-input id="payment-date" type="date" wire:model="paidAt" class="tabular-nums" />
                            </x-field>
                        </div>
                    </div>

                    <div>
                        <span class="form-label d-block">Metode</span>
                        <div class="d-flex flex-wrap gap-2" role="radiogroup" aria-label="Metode">
                            @foreach (\App\Enums\PaymentMethod::options() as $value => $label)
                                <input type="radio" class="btn-check" id="method-{{ $value }}" wire:model="method" value="{{ $value }}" wire:key="method-{{ $value }}">
                                <label class="btn btn-outline-primary" for="method-{{ $value }}" wire:key="method-label-{{ $value }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        @error('method') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <x-field label="Catatan (opsional)" for="payment-note" error="note">
                        <x-input id="payment-note" wire:model="note" placeholder="cth. DP via BCA" />
                    </x-field>
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <x-button variant="ghost" :href="$backHref">Batal</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $kind === 'refund' ? 'Simpan pengembalian' : 'Simpan pembayaran' }}</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
