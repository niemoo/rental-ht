@php($backHref = $rental ? route('rentals.show', $rental) : route('dashboard'))
<div class="flex flex-col gap-4">
    <div>
        <a href="{{ $backHref }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">{{ $rental ? 'Ubah penyewaan' : 'Penyewaan baru' }}</h1>
    </div>

    <form wire:submit="save" class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
        {{-- Form column --}}
        <div class="flex min-w-0 flex-col gap-4">
            {{-- 1 · Dates --}}
            <section class="rounded-xl border border-border bg-card p-4">
                <div class="mb-3 flex items-baseline justify-between gap-3">
                    <h2 class="text-sm font-semibold">1 · Tanggal</h2>
                    @if ($this->durationInDays)
                        <span class="text-xs tabular-nums text-muted-foreground">{{ $this->durationInDays }} hari</span>
                    @endif
                </div>
                <div class="grid max-w-xl grid-cols-2 gap-3">
                    <x-field label="Tanggal mulai" for="start-date" error="startDate">
                        <x-input id="start-date" type="date" wire:model.live="startDate" class="tabular-nums" />
                    </x-field>
                    <x-field label="Tanggal kembali" for="due-date" error="dueDate">
                        <x-input id="due-date" type="date" wire:model.live="dueDate" class="tabular-nums" />
                    </x-field>
                </div>
            </section>

            {{-- 2 · Units --}}
            <section class="rounded-xl border border-border bg-card p-4">
                <div class="mb-3 flex items-baseline justify-between gap-3">
                    <h2 class="text-sm font-semibold">2 · Unit</h2>
                    @if ($this->datesAreValid)
                        <span class="text-xs tabular-nums text-muted-foreground">{{ count($unitIds) }} dipilih · {{ $this->availableUnits->count() }} tersedia</span>
                    @endif
                </div>
                @if ($this->returnedUnits->isNotEmpty())
                    <div class="mb-4">
                        <h3 class="mb-2 text-xs font-medium text-muted-foreground">Sudah dikembalikan · tidak bisa diubah</h3>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($this->returnedUnits as $unit)
                                <x-unit-chip :code="$unit->code" wire:key="returned-unit-{{ $unit->id }}" />
                            @endforeach
                        </div>
                    </div>
                @endif
                @if (! $this->datesAreValid)
                    <p class="text-sm text-muted-foreground">Pilih tanggal mulai dan kembali untuk melihat unit yang tersedia.</p>
                @elseif ($this->availableUnits->isEmpty())
                    <p class="text-sm text-muted-foreground">Tidak ada unit yang tersedia untuk tanggal tersebut.</p>
                @else
                    <div class="flex flex-col gap-4">
                        @foreach ($this->availableUnitsByCategory as $categoryName => $units)
                            <div wire:key="unit-category-{{ $categoryName }}">
                                <h3 class="mb-2 text-xs font-medium text-muted-foreground">
                                    {{ $categoryName }} · <span class="tabular-nums">{{ $units->count() }} tersedia</span>
                                </h3>
                                <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 xl:grid-cols-10">
                                    @foreach ($units as $unit)
                                        <label wire:key="unit-option-{{ $unit->id }}" class="cursor-pointer rounded-md border border-input bg-background py-2 text-center text-[13px] font-medium tabular-nums text-muted-foreground transition-colors select-none hover:border-primary hover:text-primary has-checked:border-primary has-checked:bg-primary has-checked:text-primary-foreground">
                                            <input type="checkbox" wire:model.live="unitIds" value="{{ $unit->id }}" class="sr-only">
                                            {{ $unit->code }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @error('unitIds') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </section>

            {{-- 3 · Customer --}}
            <section class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 text-sm font-semibold">3 · Pelanggan</h2>

                @can(\App\Enums\Permission::CreateCustomers)
                <div class="mb-3 inline-flex gap-0.5 rounded-lg border border-border bg-muted/60 p-0.5">
                    @foreach (['existing' => 'Pelanggan lama', 'new' => 'Pelanggan baru'] as $mode => $label)
                        <button
                            type="button"
                            wire:click="$set('customerMode', '{{ $mode }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
                                'bg-card text-foreground shadow-sm' => $customerMode === $mode,
                                'text-muted-foreground' => $customerMode !== $mode,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>
                @endcan

                @if ($customerMode === 'existing')
                    <div class="flex flex-col gap-2">
                        <x-search-input wire:model.live.debounce.250ms="customerSearch" placeholder="Cari nama atau nomor…" />
                        <div class="max-h-52 overflow-y-auto rounded-md border border-border">
                            @forelse ($this->customers as $customer)
                                <label wire:key="customer-option-{{ $customer->id }}" class="flex w-full cursor-pointer items-center justify-between border-b border-border px-3 py-2.5 text-left last:border-b-0 hover:bg-muted/60 has-checked:bg-accent">
                                    <input type="radio" wire:model.live="customerId" value="{{ $customer->id }}" class="sr-only">
                                    <span class="text-sm font-medium">{{ $customer->name }}</span>
                                    <span class="text-xs tabular-nums text-muted-foreground">{{ $customer->phone }}</span>
                                </label>
                            @empty
                                <p class="p-3 text-sm text-muted-foreground">Tidak ada pelanggan yang cocok.</p>
                            @endforelse
                        </div>
                        @error('customerId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-field label="Nama" for="customer-name" error="customerName">
                            <x-input id="customer-name" wire:model.blur="customerName" placeholder="Nama lengkap" />
                        </x-field>
                        <x-field label="Nomor HP" for="customer-phone" error="customerPhone">
                            <x-input id="customer-phone" wire:model="customerPhone" placeholder="08xx-xxxx-xxxx" inputmode="tel" />
                        </x-field>
                    </div>
                @endif
            </section>

            {{-- Event --}}
            <section class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 text-sm font-semibold">Detail acara (opsional)</h2>
                <div class="grid gap-3 md:grid-cols-3">
                    <x-field label="Nama acara" for="event-name" error="eventName">
                        <x-input id="event-name" wire:model="eventName" placeholder="cth. Pernikahan — Sinta & Andi" />
                    </x-field>
                    <x-field label="Alamat acara" for="event-address" error="eventAddress">
                        <x-input id="event-address" wire:model="eventAddress" placeholder="Lokasi / alamat" />
                    </x-field>
                    <x-field label="Media sosial acara" for="event-social" error="eventSocial">
                        <x-input id="event-social" wire:model="eventSocial" placeholder="@akun atau nama halaman" />
                    </x-field>
                </div>
            </section>

            {{-- Discount --}}
            @can(\App\Enums\Permission::GiveDiscounts)
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2 class="mb-3 text-sm font-semibold">Diskon (opsional)</h2>
                    <div class="grid gap-3 md:grid-cols-[auto_minmax(0,12rem)_minmax(0,1fr)] md:items-start">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-sm leading-none font-medium">Jenis</span>
                            <div class="inline-flex gap-0.5 rounded-lg border border-border bg-muted/60 p-0.5">
                                @foreach (['' => 'Tanpa diskon', 'amount' => 'Rupiah', 'percent' => 'Persen'] as $type => $label)
                                    <button
                                        type="button"
                                        wire:click="$set('discountType', '{{ $type }}')"
                                        @class([
                                            'rounded-md px-3 py-1.5 text-[13px] font-medium whitespace-nowrap transition-colors',
                                            'bg-card text-foreground shadow-sm' => $discountType === $type,
                                            'text-muted-foreground' => $discountType !== $type,
                                        ])
                                    >{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        @if ($discountType !== '')
                            <x-field :label="$discountType === 'percent' ? 'Besar diskon (%)' : 'Besar diskon'" for="discount-value" error="discountValue">
                                @if ($discountType === 'percent')
                                    <x-input id="discount-value" type="number" min="0" max="100" step="1" inputmode="numeric" wire:model.live.debounce.400ms="discountValue" placeholder="10" class="tabular-nums" />
                                @else
                                    <x-money-input id="discount-value" wire:model.live.debounce.400ms="discountValue" placeholder="50000" />
                                @endif
                            </x-field>
                            <x-field label="Alasan" for="discount-note" error="discountNote">
                                <x-input id="discount-note" wire:model="discountNote" placeholder="cth. pelanggan langganan" />
                            </x-field>
                        @endif
                    </div>
                </section>
            @endcan
        </div>

        {{-- Sticky summary --}}
        <aside class="flex flex-col gap-3 rounded-xl border border-border bg-card p-4 lg:sticky lg:top-24">
            <h2 class="text-sm font-semibold">Ringkasan</h2>

            <dl class="flex flex-col gap-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-muted-foreground">Pelanggan</dt>
                    <dd class="text-right font-medium">{{ $this->customerLabel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted-foreground">Tanggal</dt>
                    <dd class="text-right tabular-nums">
                        @if ($this->datesAreValid)
                            {{ \Carbon\Carbon::parse($startDate)->translatedFormat('j M') }} → {{ \Carbon\Carbon::parse($dueDate)->translatedFormat('j M') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted-foreground">Durasi</dt>
                    <dd class="text-right tabular-nums">{{ $this->durationInDays ? $this->durationInDays.' hari' : '—' }}</dd>
                </div>
                @if (filled($eventName))
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">Acara</dt>
                        <dd class="text-right">{{ $eventName }}</dd>
                    </div>
                @endif
            </dl>

            <div class="h-px bg-border"></div>

            <div class="flex flex-col gap-2">
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Unit</span>
                    <span class="font-medium tabular-nums">{{ $this->selectedUnits->count() }}</span>
                </div>
                @if ($this->selectedUnits->isNotEmpty())
                    <div class="flex max-h-32 flex-wrap gap-1.5 overflow-y-auto">
                        @foreach ($this->selectedUnits as $unit)
                            <x-unit-chip :code="$unit->code" wire:key="summary-unit-{{ $unit->id }}" />
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-muted-foreground">Belum ada unit dipilih.</p>
                @endif
            </div>

            @if ($this->estimate && $this->estimate['lines'] !== [])
                <div class="h-px bg-border"></div>

                <dl class="flex flex-col gap-1.5 text-sm">
                    @foreach ($this->estimate['lines'] as $line)
                        <div class="flex justify-between gap-3" wire:key="estimate-{{ $line['category'] }}-{{ $line['rate'] }}">
                            <dt class="text-muted-foreground">
                                {{ $line['category'] }} <span class="tabular-nums">· {{ $line['quantity'] }} × {{ \App\Support\RentalBill::rupiah($line['rate']) }} × {{ $this->durationInDays }}h</span>
                            </dt>
                            <dd class="text-right tabular-nums">{{ \App\Support\RentalBill::rupiah($line['amount']) }}</dd>
                        </div>
                    @endforeach
                    @if ($this->estimate['discount'] > 0)
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Diskon{{ $discountType === 'percent' ? ' '.(int) $discountValue.'%' : '' }}</dt>
                            <dd class="text-right text-red-600 tabular-nums dark:text-red-400">−{{ \App\Support\RentalBill::rupiah($this->estimate['discount']) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3 border-t border-border pt-1.5">
                        <dt class="font-medium">Total sewa</dt>
                        <dd class="text-right font-semibold tabular-nums">{{ \App\Support\RentalBill::rupiah($this->estimate['total']) }}</dd>
                    </div>
                </dl>
                @if ($rental?->charges()->exists())
                    <p class="text-xs text-muted-foreground">Belum termasuk biaya tambahan.</p>
                @endif
            @endif

            <div class="h-px bg-border"></div>

            <x-button type="submit" class="h-9 w-full" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $rental ? 'Simpan perubahan' : 'Buat penyewaan' }}</span>
                <span wire:loading wire:target="save">{{ $rental ? 'Menyimpan…' : 'Membuat…' }}</span>
            </x-button>
            <x-button variant="ghost" :href="$backHref" class="w-full">Batal</x-button>
        </aside>
    </form>
</div>
