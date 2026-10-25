@props(['rental'])

{{--
    Requires the host Livewire component to use App\Livewire\Concerns\ReturnsRentals. Hidden without the permission.
    Staff pick the condition each unit came back in; it starts at the unit's current condition.
--}}
@can(\App\Enums\Permission::ReturnRentals)
@php
    $openItems = $rental->relationLoaded('openItems')
        ? $rental->openItems
        : ($rental->relationLoaded('items') ? $rental->items->reject->isReturned()->values() : $rental->openItems);
    $openItems->loadMissing('unit');
@endphp
<div
    x-data="{ open: false, loading: false, conditions: @js($openItems->mapWithKeys(fn ($item) => [$item->id => $item->unit->condition->value])) }"
    class="inline-block text-left"
>
    <x-button variant="outline" size="sm" x-on:click="open = true">Kembalikan</x-button>

    <x-modal title="Kembalikan unit" size="md">
        <p class="text-sm text-muted-foreground">Kembalikan semua unit pada penyewaan {{ $rental->customer->name }}. Pilih kondisi tiap unit saat kembali.</p>

        <div class="mt-3 flex max-h-72 flex-col divide-y divide-border overflow-y-auto rounded-lg border border-border">
            @foreach ($openItems as $item)
                <label wire:key="return-{{ $rental->id }}-item-{{ $item->id }}" class="flex items-center justify-between gap-3 px-3 py-2">
                    <x-unit-chip :code="$item->unit->code" />
                    <select
                        x-model="conditions[{{ $item->id }}]"
                        aria-label="Kondisi {{ $item->unit->code }}"
                        class="h-8 rounded-lg border border-input bg-white px-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
                    >
                        @foreach (\App\Enums\UnitCondition::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach
        </div>

        <x-slot:footer>
            <x-button variant="ghost" x-on:click="open = false" x-bind:disabled="loading">Batal</x-button>
            <x-button
                x-on:click="loading = true; $wire.returnRental({{ $rental->id }}, conditions).then(() => { loading = false; open = false })"
                x-bind:disabled="loading"
            >
                <span x-text="loading ? 'Mengembalikan…' : 'Konfirmasi pengembalian'">Konfirmasi pengembalian</span>
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>
@endcan
