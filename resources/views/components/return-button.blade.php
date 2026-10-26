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
    class="d-inline-block"
>
    <button type="button" class="btn btn-soft-success btn-sm" x-on:click="open = true">Kembalikan</button>

    <x-modal title="Kembalikan unit" size="md">
        <p class="text-muted">Kembalikan semua unit pada penyewaan {{ $rental->customer->name }}. Pilih kondisi tiap unit saat kembali.</p>

        <div class="vstack gap-2">
            @foreach ($openItems as $item)
                <div wire:key="return-{{ $rental->id }}-item-{{ $item->id }}" class="d-flex align-items-center justify-content-between gap-3 border rounded px-3 py-2">
                    <x-unit-chip :code="$item->unit->code" />
                    <div style="width: 12rem">
                        <x-select x-model="conditions[{{ $item->id }}]" aria-label="Kondisi {{ $item->unit->code }}" :options="\App\Enums\UnitCondition::options()" />
                    </div>
                </div>
            @endforeach
        </div>

        <x-slot:footer>
            <x-button variant="outline" x-on:click="open = false" x-bind:disabled="loading">Batal</x-button>
            <x-button
                variant="success"
                x-on:click="loading = true; $wire.returnRental({{ $rental->id }}, conditions).then(() => { loading = false; open = false })"
                x-bind:disabled="loading"
            >
                <span x-text="loading ? 'Mengembalikan…' : 'Konfirmasi pengembalian'">Konfirmasi pengembalian</span>
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>
@endcan
