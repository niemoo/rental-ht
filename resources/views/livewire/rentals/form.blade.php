@php
    $backHref = $rental ? route('rentals.show', $rental) : route('dashboard');
@endphp
<div class="vstack gap-4">
    <x-page-header
        :title="$rental ? 'Ubah penyewaan' : 'Penyewaan baru'"
        :subtitle="$rental ? $rental->invoice_number.' · '.$rental->customer->name : 'Pilih tanggal, unit, dan pelanggan. Ringkasan dan total ada di samping.'"
    >
        <x-slot:action>
            <x-button variant="outline" :href="$backHref"><x-icon name="arrow-left" />Kembali</x-button>
        </x-slot:action>
    </x-page-header>

    <form
        x-data="confirmAction(@js([
            'text' => $rental ? 'Perubahan pada penyewaan '.$rental->invoice_number.' akan disimpan.' : 'Penyewaan baru akan dibuat sesuai ringkasan.',
            'confirmLabel' => $rental ? 'Ya, simpan perubahan' : 'Ya, buat penyewaan',
        ]))"
        x-on:submit.prevent="ask('save')"
        class="row g-4 align-items-start"
    >
        {{-- Form column --}}
        <div class="col-xl-8">
            <div class="vstack gap-4">
                {{-- 1 · Dates --}}
                <div class="card mb-0">
                    <div class="card-header d-flex align-items-center gap-2">
                        <h5 class="card-title mb-0 flex-grow-1">1 · Tanggal</h5>
                        @if ($this->durationInDays)
                            <span class="badge bg-primary-subtle text-primary tabular-nums">{{ Number::format($this->durationInDays) }} hari</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <x-field label="Tanggal mulai" for="start-date" error="startDate">
                                    <x-input id="start-date" type="date" wire:model.live="startDate" class="tabular-nums" />
                                </x-field>
                            </div>
                            <div class="col-sm-6">
                                <x-field label="Tanggal kembali" for="due-date" error="dueDate">
                                    <x-input id="due-date" type="date" wire:model.live="dueDate" class="tabular-nums" />
                                </x-field>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2 · Units --}}
                <div class="card mb-0">
                    <div class="card-header d-flex align-items-center gap-2">
                        <h5 class="card-title mb-0 flex-grow-1">2 · Unit</h5>
                        @if ($this->datesAreValid)
                            <span class="text-muted fs-13 tabular-nums">{{ Number::format(count($unitIds)) }} dipilih · {{ Number::format($this->availableUnits->count()) }} tersedia</span>
                        @endif
                    </div>
                    <div class="card-body vstack gap-4">
                        @if ($this->returnedUnits->isNotEmpty())
                            <div>
                                <h6 class="fs-13 text-muted mb-2">Sudah dikembalikan · tidak bisa diubah</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($this->returnedUnits as $unit)
                                        <x-unit-chip :code="$unit->code" wire:key="returned-unit-{{ $unit->id }}" />
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! $this->datesAreValid)
                            <p class="text-muted mb-0">Pilih tanggal mulai dan kembali untuk melihat unit yang tersedia.</p>
                        @elseif ($this->availableUnits->isEmpty())
                            <p class="text-muted mb-0">Tidak ada unit yang tersedia untuk tanggal tersebut.</p>
                        @else
                            @foreach ($this->availableUnitsByCategory as $categoryName => $units)
                                <div wire:key="unit-category-{{ $categoryName }}">
                                    <h6 class="fs-13 mb-2">
                                        {{ $categoryName }} · <span class="text-muted fw-normal tabular-nums">{{ Number::format($units->count()) }} tersedia</span>
                                    </h6>
                                    <div class="d-flex flex-wrap gap-2 unit-picker">
                                        @foreach ($units as $unit)
                                            <div wire:key="unit-option-{{ $unit->id }}">
                                                <input type="checkbox" class="btn-check" id="unit-option-{{ $unit->id }}" wire:model.live="unitIds" value="{{ $unit->id }}" autocomplete="off">
                                                <label class="btn btn-outline-primary btn-sm tabular-nums" for="unit-option-{{ $unit->id }}">{{ $unit->code }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @error('unitIds') <div class="invalid-feedback d-block mt-0">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- 3 · Customer --}}
                <div class="card mb-0">
                    <div class="card-header d-flex flex-wrap align-items-center gap-2">
                        <h5 class="card-title mb-0 flex-grow-1">3 · Pelanggan</h5>
                        @can(\App\Enums\Permission::CreateCustomers)
                            <div class="btn-group btn-group-sm" role="group" aria-label="Jenis pelanggan">
                                @foreach (['existing' => 'Pelanggan lama', 'new' => 'Pelanggan baru'] as $mode => $label)
                                    <button
                                        type="button"
                                        wire:click="$set('customerMode', '{{ $mode }}')"
                                        @class(['btn', 'btn-primary' => $customerMode === $mode, 'btn-outline-primary' => $customerMode !== $mode])
                                        aria-pressed="{{ $customerMode === $mode ? 'true' : 'false' }}"
                                    >{{ $label }}</button>
                                @endforeach
                            </div>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if ($customerMode === 'existing')
                            <div class="vstack gap-2">
                                <x-search-input wire:model.live.debounce.250ms="customerSearch" placeholder="Cari nama atau nomor…" />
                                <div class="list-group" style="max-height: 15rem; overflow-y: auto">
                                    @forelse ($this->customers as $customer)
                                        <label wire:key="customer-option-{{ $customer->id }}" @class(['list-group-item list-group-item-action d-flex align-items-center gap-2 mb-0', 'active' => (string) $customerId === (string) $customer->id])>
                                            <input type="radio" class="form-check-input m-0 flex-shrink-0" wire:model.live="customerId" value="{{ $customer->id }}">
                                            <span class="flex-grow-1 fw-medium">{{ $customer->name }}</span>
                                            <span class="fs-13 tabular-nums">{{ $customer->phone }}</span>
                                        </label>
                                    @empty
                                        <div class="list-group-item text-muted">Tidak ada pelanggan yang cocok.</div>
                                    @endforelse
                                </div>
                                @error('customerId') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <x-field label="Nama" for="customer-name" error="customerName">
                                        <x-input id="customer-name" wire:model.blur="customerName" placeholder="Nama lengkap" />
                                    </x-field>
                                </div>
                                <div class="col-sm-6">
                                    <x-field label="Nomor HP" for="customer-phone" error="customerPhone">
                                        <x-input id="customer-phone" wire:model="customerPhone" placeholder="08xx-xxxx-xxxx" inputmode="tel" />
                                    </x-field>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Event --}}
                <div class="card mb-0">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detail acara <span class="text-muted fw-normal fs-13">(opsional)</span></h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <x-field label="Nama acara" for="event-name" error="eventName">
                                    <x-input id="event-name" wire:model.blur="eventName" placeholder="cth. Pernikahan — Sinta & Andi" />
                                </x-field>
                            </div>
                            <div class="col-md-6">
                                <x-field label="Alamat acara" for="event-address" error="eventAddress">
                                    <x-input id="event-address" wire:model="eventAddress" placeholder="Lokasi / alamat" />
                                </x-field>
                            </div>
                            <div class="col-md-6">
                                <x-field label="Media sosial acara" for="event-social" error="eventSocial">
                                    <x-input id="event-social" wire:model="eventSocial" placeholder="@akun atau nama halaman" />
                                </x-field>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Discount --}}
                @can(\App\Enums\Permission::GiveDiscounts)
                    <div class="card mb-0">
                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                            <h5 class="card-title mb-0 flex-grow-1">Diskon (opsional)</h5>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Jenis diskon">
                                @foreach (['' => 'Tanpa diskon', 'amount' => 'Rupiah', 'percent' => 'Persen'] as $type => $label)
                                    <button
                                        type="button"
                                        wire:click="$set('discountType', '{{ $type }}')"
                                        @class(['btn text-nowrap', 'btn-primary' => $discountType === $type, 'btn-outline-primary' => $discountType !== $type])
                                        aria-pressed="{{ $discountType === $type ? 'true' : 'false' }}"
                                    >{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        @if ($discountType !== '')
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <x-field :label="$discountType === 'percent' ? 'Besar diskon (%)' : 'Besar diskon'" for="discount-value" error="discountValue">
                                            @if ($discountType === 'percent')
                                                <div class="input-group" wire:key="discount-value-percent">
                                                    <x-input id="discount-value" type="number" min="0" max="100" step="1" inputmode="numeric" wire:model.live.debounce.400ms="discountValue" placeholder="10" class="tabular-nums" />
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            @else
                                                <x-money-input id="discount-value" wire:model.live.debounce.400ms="discountValue" placeholder="50000" />
                                            @endif
                                        </x-field>
                                    </div>
                                    <div class="col-md-7">
                                        <x-field label="Alasan" for="discount-note" error="discountNote">
                                            <x-input id="discount-note" wire:model="discountNote" placeholder="cth. pelanggan langganan" />
                                        </x-field>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endcan
            </div>
        </div>

        {{-- Sticky summary --}}
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
                                <td class="text-end pe-0 fw-medium">{{ $this->customerLabel ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Tanggal</td>
                                <td class="text-end pe-0 tabular-nums">
                                    @if ($this->datesAreValid)
                                        {{ \Carbon\Carbon::parse($startDate)->translatedFormat('j M') }} → {{ \Carbon\Carbon::parse($dueDate)->translatedFormat('j M') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Durasi</td>
                                <td class="text-end pe-0 tabular-nums">{{ $this->durationInDays ? Number::format($this->durationInDays).' hari' : '—' }}</td>
                            </tr>
                            @if (filled($eventName))
                                <tr>
                                    <td class="text-muted ps-0">Acara</td>
                                    <td class="text-end pe-0 text-break">{{ $eventName }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="card-body border-top border-top-dashed">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Unit</span>
                        <span class="fw-medium tabular-nums">{{ Number::format($this->selectedUnits->count()) }}</span>
                    </div>
                    @if ($this->selectedUnits->isNotEmpty())
                        <div class="d-flex flex-wrap gap-1 overflow-y-auto" style="max-height: 8rem">
                            @foreach ($this->selectedUnits as $unit)
                                <x-unit-chip :code="$unit->code" wire:key="summary-unit-{{ $unit->id }}" />
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted fs-13 mb-0">Belum ada unit dipilih.</p>
                    @endif
                </div>

                @if ($this->estimate && $this->estimate['lines'] !== [])
                    <div class="card-body border-top border-top-dashed">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                @foreach ($this->estimate['lines'] as $line)
                                    <tr wire:key="estimate-{{ $line['category'] }}-{{ $line['rate'] }}">
                                        <td class="ps-0">
                                            {{ $line['category'] }}
                                            <span class="text-muted fs-12 tabular-nums">· {{ Number::format($line['quantity']) }} × {{ \App\Support\RentalBill::rupiah($line['rate']) }} × {{ Number::format($this->durationInDays) }}h</span>
                                        </td>
                                        <td class="text-end pe-0 tabular-nums">{{ \App\Support\RentalBill::rupiah($line['amount']) }}</td>
                                    </tr>
                                @endforeach
                                @if ($this->estimate['discount'] > 0)
                                    <tr>
                                        <td class="ps-0">Diskon{{ $discountType === 'percent' ? ' '.(int) $discountValue.'%' : '' }}</td>
                                        <td class="text-end pe-0 tabular-nums text-danger">−{{ \App\Support\RentalBill::rupiah($this->estimate['discount']) }}</td>
                                    </tr>
                                @endif
                                <tr class="border-top border-top-dashed">
                                    <th class="ps-0 pt-2">Total sewa</th>
                                    <th class="text-end pe-0 pt-2 tabular-nums fs-15">{{ \App\Support\RentalBill::rupiah($this->estimate['total']) }}</th>
                                </tr>
                            </tbody>
                        </table>
                        @if ($rental?->charges()->exists())
                            <p class="text-muted fs-12 mb-0 mt-2">Belum termasuk biaya tambahan.</p>
                        @endif
                    </div>
                @endif

                <div class="card-footer vstack gap-2">
                    <x-button type="submit" class="w-100" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $rental ? 'Simpan perubahan' : 'Buat penyewaan' }}</span>
                        <span wire:loading wire:target="save">{{ $rental ? 'Menyimpan…' : 'Membuat…' }}</span>
                    </x-button>
                    <x-button variant="ghost" :href="$backHref" class="w-100">Batal</x-button>
                </div>
            </div>
        </div>
    </form>
</div>
