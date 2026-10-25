<div class="flex flex-col gap-4">
    <div>
        <a href="{{ route('rentals.show', $rental) }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">Tambah biaya</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ $rental->customer->name }} · <span class="tabular-nums">{{ $rental->invoice_number }}</span></p>
    </div>

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4 rounded-xl border border-border bg-card p-4">
        <div class="flex flex-col gap-1.5">
            <span class="text-sm leading-none font-medium">Jenis biaya</span>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Enums\ChargeType::options() as $value => $label)
                    <label wire:key="type-{{ $value }}" class="cursor-pointer rounded-lg border border-input bg-background px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors select-none hover:border-primary hover:text-primary has-checked:border-primary has-checked:bg-primary has-checked:text-primary-foreground">
                        <input type="radio" wire:model.live="type" value="{{ $value }}" class="sr-only">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('type') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if ($type === \App\Enums\ChargeType::LateFee->value)
            <div class="rounded-lg border border-border bg-muted/50 px-3 py-2.5 text-sm">
                @if ($this->lateFee['amount'] > 0)
                    Saran: telat <span class="font-medium tabular-nums">{{ $this->lateFee['days'] }} hari</span> × tarif harian tiap unit =
                    <button type="button" wire:click="$set('amount', '{{ $this->lateFee['amount'] }}')" class="font-semibold text-primary tabular-nums hover:underline">{{ \App\Support\RentalBill::rupiah($this->lateFee['amount']) }}</button>
                @else
                    <span class="text-muted-foreground">Belum ada unit yang terlambat, jadi tidak ada saran denda.</span>
                @endif
            </div>
        @endif

        <x-field label="Jumlah" for="charge-amount" error="amount">
            <x-money-input id="charge-amount" wire:model="amount" placeholder="50000" />
        </x-field>

        <x-field label="Keterangan (opsional)" for="charge-description" error="description" hint="Tampil di invoice. Kosongkan untuk memakai nama jenis biaya.">
            <x-input id="charge-description" wire:model="description" placeholder="cth. Antenna HT A-012 patah" />
        </x-field>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <x-button variant="ghost" :href="route('rentals.show', $rental)">Batal</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Simpan biaya</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
