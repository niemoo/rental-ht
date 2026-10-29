@php
    $backHref = route('rentals.show', $rental);
    $total = $this->openItems->count();
@endphp
<div class="vstack gap-4">
    <x-page-header title="Kembalikan unit" :subtitle="$rental->invoice_number.' · '.$rental->customer->name">
        <x-slot:action>
            <x-button variant="outline" :href="$backHref"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <form
        x-data="confirmAction(@js([
            'text' => Number::format($total).' unit di penyewaan '.$rental->customer->name.' akan dicatat sudah kembali dengan kondisi yang dipilih.',
            'confirmLabel' => 'Ya, kembalikan',
        ]))"
        x-on:submit.prevent="ask('save')"
        class="row g-4 align-items-start"
    >
        <div class="col-xl-8">
            <div class="card mb-0" x-data="{ search: '' }">
                <div class="card-header d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">Unit yang kembali</h5>
                        <p class="text-muted fs-13 mb-0">Kondisi awalnya sama dengan kondisi unit sekarang. Ubah yang kembali rusak atau hilang.</p>
                    </div>
                </div>

                <div class="card-body border-bottom border-bottom-dashed d-flex flex-wrap align-items-center gap-2">
                    <div class="search-box flex-grow-1" style="min-width: 12rem">
                        <input type="search" class="form-control" placeholder="Cari kode unit…" aria-label="Cari kode unit" x-model="search">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fs-13 text-nowrap">Atur semua jadi</span>
                        <div style="width: 12rem">
                            <x-select
                                id="condition-all"
                                aria-label="Atur kondisi semua unit"
                                :options="['' => 'Pilih kondisi'] + \App\Enums\UnitCondition::options()"
                                x-on:change="$event.target.value && $wire.setAllConditions($event.target.value)"
                            />
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col">Unit</th>
                                <th scope="col">Kategori</th>
                                <th scope="col" style="width: 13rem">Kondisi saat kembali</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->openItems as $item)
                                <tr wire:key="return-item-{{ $item->id }}" x-show="@js(mb_strtolower($item->unit->code)).includes(search.trim().toLowerCase())">
                                    <td><x-unit-chip :code="$item->unit->code" /></td>
                                    <td>{{ $item->unit->category->name }}</td>
                                    <td>
                                        <x-select
                                            id="condition-{{ $item->id }}"
                                            size="sm"
                                            wire:model.live="conditions.{{ $item->id }}"
                                            aria-label="Kondisi {{ $item->unit->code }}"
                                            :options="\App\Enums\UnitCondition::options()"
                                        />
                                        @error('conditions.'.$item->id)
                                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4 summary-sticky">
            <div class="card mb-0">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ringkasan</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted ps-0">Pelanggan</td>
                                <td class="text-end pe-0 fw-medium">{{ $rental->customer->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Tanggal</td>
                                <td class="text-end pe-0 tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Unit dikembalikan</td>
                                <td class="text-end pe-0 fw-medium tabular-nums">{{ Number::format($total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-body border-top border-top-dashed vstack gap-2">
                    @foreach ($this->conditionCounts as $value => $count)
                        @php($condition = \App\Enums\UnitCondition::from($value))
                        <div wire:key="count-{{ $value }}" class="d-flex align-items-center justify-content-between">
                            <span class="badge {{ $condition->badgeClass() }}">{{ $condition->label() }}</span>
                            <span class="fw-medium tabular-nums">{{ Number::format($count) }} unit</span>
                        </div>
                    @endforeach
                    @php($unusable = collect($this->conditionCounts)->except(\App\Enums\UnitCondition::Good->value)->sum())
                    @if ($unusable > 0)
                        <div class="alert alert-warning alert-border-left fs-13 mb-0 mt-1" role="status">
                            {{ Number::format($unusable) }} unit tidak bisa disewakan lagi sampai kondisinya diubah jadi Baik di halaman Unit.
                        </div>
                    @endif
                </div>

                <div class="card-footer vstack gap-2">
                    <x-button type="submit" variant="success" class="w-100" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Kembalikan {{ Number::format($total) }} unit</span>
                        <span wire:loading wire:target="save">Mengembalikan…</span>
                    </x-button>
                    <x-button variant="ghost" :href="$backHref" class="w-100">Batal</x-button>
                </div>
            </div>
        </div>
    </form>
</div>
