@props(['rentals'])

{{-- "Mendatang" card: booked rentals that haven't started, as a table on desktop and a list on mobile. Expects customer + openItems.unit loaded. --}}
@if ($rentals->isNotEmpty())
    @php
        $canViewRentals = auth()->user()->can(\App\Enums\Permission::ViewRentals);
        $unitCount = $rentals->sum(fn ($rental) => $rental->openItems->count());
    @endphp

    <div class="card mb-0">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <h4 class="card-title mb-0 flex-grow-1">
                Mendatang <span class="text-muted fw-normal fs-13 tabular-nums">· {{ Number::format($rentals->count()) }} pemesanan, {{ Number::format($unitCount) }} unit</span>
            </h4>
        </div>

        {{-- Desktop table --}}
        <div class="card-body d-none d-lg-block">
            <div class="table-responsive table-card">
                <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th>Pelanggan</th>
                            <th>Acara</th>
                            <th>Unit</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Status</th>
                            @if ($canViewRentals)
                                <th><span class="visually-hidden">Detail</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rentals as $rental)
                            <tr wire:key="upcoming-row-{{ $rental->id }}">
                                <td>
                                    <x-link-if :href="route('rentals.show', $rental)" :when="$canViewRentals" class="fw-medium" link-class="link-primary">{{ $rental->customer->name }}</x-link-if>
                                    <div class="text-muted fs-12 tabular-nums">{{ $rental->customer->phone }}</div>
                                </td>
                                <td @class(['text-wrap', 'text-muted' => ! $rental->event_name]) style="max-width: 14rem">{{ $rental->event_name ?? '—' }}</td>
                                <td class="text-wrap" style="min-width: 10rem; max-width: 16rem">
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($rental->openItems as $item)
                                            <x-unit-chip :code="$item->unit->code" />
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium tabular-nums">{{ $rental->start_date->translatedFormat('j M') }}</div>
                                    <div class="text-secondary fs-12 tabular-nums">{{ $rental->timing()->label }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium tabular-nums">{{ $rental->due_date->translatedFormat('j M') }}</div>
                                    <div class="text-muted fs-12 tabular-nums">{{ \App\Support\RentalBill::days($rental->start_date, $rental->due_date) }} hari</div>
                                </td>
                                <td><x-status-badge status="upcoming">Mendatang</x-status-badge></td>
                                @if ($canViewRentals)
                                    <td class="text-end">
                                        <a href="{{ route('rentals.show', $rental) }}" class="btn btn-ghost-secondary btn-icon btn-sm" aria-label="Detail penyewaan {{ $rental->customer->name }}" title="Detail">
                                            <x-icon name="chevron-right" class="fs-18" />
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @foreach ($rentals as $rental)
                <li class="list-group-item p-0" wire:key="upcoming-card-{{ $rental->id }}">
                    <x-link-if
                        :href="route('rentals.show', $rental)"
                        :when="$canViewRentals"
                        as="div"
                        class="vstack gap-2 px-3 py-3 text-reset"
                        link-class="list-group-item-action"
                    >
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div style="min-width: 0">
                                <div class="fw-medium text-truncate">{{ $rental->customer->name }}</div>
                                <div class="text-muted fs-12 text-truncate">{{ $rental->event_name ?? $rental->customer->phone }}</div>
                            </div>
                            <x-status-badge status="upcoming">Mendatang</x-status-badge>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($rental->openItems as $item)
                                <x-unit-chip :code="$item->unit->code" />
                            @endforeach
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 fs-13">
                            <x-icon name="calendar-days" class="text-muted" />
                            <span class="tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }}</span>
                            <span class="text-secondary">· {{ $rental->timing()->label }}</span>
                        </div>
                    </x-link-if>
                </li>
            @endforeach
        </ul>
    </div>
@endif
