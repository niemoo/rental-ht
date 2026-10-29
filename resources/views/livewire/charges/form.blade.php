@php
    $backHref = route('rentals.show', $rental);
@endphp
<div class="vstack gap-4">
    <x-page-header title="Tambah biaya" :subtitle="$rental->invoice_number.' · '.$rental->customer->name">
        <x-slot:action>
            <x-button variant="outline" :href="$backHref"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <div class="row">
        <div class="col-xl-6 col-lg-8">
            <form
                x-data="confirmAction(@js([
                    'text' => 'Biaya ini akan ditambahkan ke tagihan '.$rental->invoice_number.' dan tampil di invoice.',
                    'confirmLabel' => 'Ya, tambahkan',
                ]))"
                x-on:submit.prevent="ask('save')"
                class="card mb-0"
            >
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail biaya</h5>
                </div>

                <div class="card-body vstack gap-3">
                    <div>
                        <span class="form-label d-block">Jenis biaya</span>
                        <div class="d-flex flex-wrap gap-2" role="radiogroup" aria-label="Jenis biaya">
                            @foreach (\App\Enums\ChargeType::options() as $value => $label)
                                <input type="radio" class="btn-check" id="type-{{ $value }}" wire:model.live="type" value="{{ $value }}" wire:key="type-{{ $value }}">
                                <label class="btn btn-outline-primary" for="type-{{ $value }}" wire:key="type-label-{{ $value }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        @error('type') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                    </div>

                    @if ($type === \App\Enums\ChargeType::LateFee->value)
                        <div class="alert alert-info alert-border-left mb-0" role="status">
                            @if ($this->lateFee['amount'] > 0)
                                Saran: telat <strong class="tabular-nums">{{ Number::format($this->lateFee['days']) }} hari</strong> × tarif harian tiap unit =
                                <button type="button" wire:click="$set('amount', '{{ $this->lateFee['amount'] }}')" class="btn btn-link p-0 align-baseline fw-semibold tabular-nums">{{ \App\Support\RentalBill::rupiah($this->lateFee['amount']) }}</button>
                                <span class="d-block fs-12 mt-1">Klik jumlahnya untuk memakai saran ini.</span>
                            @else
                                Belum ada unit yang terlambat, jadi tidak ada saran denda.
                            @endif
                        </div>
                    @endif

                    <x-field label="Jumlah" for="charge-amount" error="amount">
                        <x-money-input id="charge-amount" wire:model="amount" placeholder="50000" />
                    </x-field>

                    <x-field label="Keterangan (opsional)" for="charge-description" error="description" hint="Tampil di invoice. Kosongkan untuk memakai nama jenis biaya.">
                        <x-input id="charge-description" wire:model="description" placeholder="cth. Antenna HT A-012 patah" />
                    </x-field>
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <x-button variant="ghost" :href="$backHref">Batal</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Simpan biaya</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
