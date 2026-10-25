@php($bill = $this->bill)
<div class="flex flex-col gap-4">
    <div>
        <a href="{{ route('rentals.show', $rental) }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">Catat pembayaran</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ $rental->customer->name }} · <span class="tabular-nums">{{ $rental->invoice_number }}</span></p>
    </div>

    <div class="grid max-w-xl grid-cols-3 gap-3 rounded-xl border border-border bg-card p-4 text-sm">
        <div><div class="text-muted-foreground">Total</div><div class="font-medium tabular-nums">{{ \App\Support\RentalBill::rupiah($bill->total) }}</div></div>
        <div><div class="text-muted-foreground">Dibayar</div><div class="font-medium tabular-nums">{{ \App\Support\RentalBill::rupiah($bill->paid) }}</div></div>
        <div>
            <div class="text-muted-foreground">{{ $bill->balance() < 0 ? 'Lebih bayar' : 'Sisa' }}</div>
            <div @class(['font-semibold tabular-nums', 'text-violet-600 dark:text-violet-400' => $bill->balance() < 0])>{{ \App\Support\RentalBill::rupiah(abs($bill->balance())) }}</div>
        </div>
    </div>

    <form wire:submit="save" class="flex max-w-xl flex-col gap-4 rounded-xl border border-border bg-card p-4">
        @if ($bill->balance() < 0)
            <div class="flex flex-col gap-1.5">
                <span class="text-sm leading-none font-medium">Jenis</span>
                <div class="inline-flex w-fit gap-0.5 rounded-lg border border-border bg-muted/60 p-0.5">
                    @foreach (['payment' => 'Pembayaran', 'refund' => 'Pengembalian dana'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('kind', '{{ $value }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
                                'bg-card text-foreground shadow-sm' => $kind === $value,
                                'text-muted-foreground' => $kind !== $value,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="$kind === 'refund' ? 'Jumlah dikembalikan' : 'Jumlah dibayar'" for="payment-amount" error="amount">
                <x-money-input id="payment-amount" wire:model="amount" placeholder="500000" autofocus />
            </x-field>
            <x-field label="Tanggal" for="payment-date" error="paidAt">
                <x-input id="payment-date" type="date" wire:model="paidAt" class="tabular-nums" />
            </x-field>
        </div>

        <div class="flex flex-col gap-1.5">
            <span class="text-sm leading-none font-medium">Metode</span>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Enums\PaymentMethod::options() as $value => $label)
                    <label wire:key="method-{{ $value }}" class="cursor-pointer rounded-lg border border-input bg-background px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors select-none hover:border-primary hover:text-primary has-checked:border-primary has-checked:bg-primary has-checked:text-primary-foreground">
                        <input type="radio" wire:model="method" value="{{ $value }}" class="sr-only">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('method') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-field label="Catatan (opsional)" for="payment-note" error="note">
            <x-input id="payment-note" wire:model="note" placeholder="cth. DP via BCA" />
        </x-field>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <x-button variant="ghost" :href="route('rentals.show', $rental)">Batal</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $kind === 'refund' ? 'Simpan pengembalian' : 'Simpan pembayaran' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
