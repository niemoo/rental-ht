@props(['rentals', 'sortBy', 'sortDirection'])

{{--
    "Sedang disewa" card: a table on desktop, a list on mobile. $rentals is a paginator with
    customer + openItems.unit loaded; the host component uses the SortsTable concern.
--}}
@php
    $rows = $rentals->map(fn ($rental) => ['rental' => $rental, 'timing' => $rental->timing()]);
    $canViewRentals = auth()->user()->can(\App\Enums\Permission::ViewRentals);
@endphp

<div class="card mb-0 h-100">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <h4 class="card-title mb-0 flex-grow-1">
            Sedang disewa <span class="text-muted fw-normal fs-13 tabular-nums">· {{ Number::format($rentals->total()) }} aktif</span>
        </h4>
        <x-sort-select
            class="d-lg-none"
            :options="['due' => 'Selesai', 'start' => 'Mulai', 'customer' => 'Pelanggan', 'units' => 'Jumlah unit']"
            :sort-by="$sortBy"
            :sort-direction="$sortDirection"
        />
        @if ($canViewRentals)
            <a href="{{ route('rentals.index') }}" class="btn btn-soft-primary btn-sm d-none d-lg-inline-block">Lihat riwayat</a>
        @endif
    </div>

    {{-- Desktop table --}}
    <div class="card-body d-none d-lg-block">
        <div class="table-responsive table-card">
            <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                        <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                        <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Mulai</x-sort-header>
                        <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Selesai</x-sort-header>
                        <th>Status</th>
                        @if ($canViewRentals)
                            <th><span class="visually-hidden">Detail</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as ['rental' => $rental, 'timing' => $timing])
                        <tr wire:key="out-row-{{ $rental->id }}" @class(['row-overdue' => $timing->status === \App\Enums\RentalStatus::Overdue])>
                            <td>
                                <x-link-if :href="route('rentals.show', $rental)" :when="$canViewRentals" class="fw-medium" link-class="link-primary">{{ $rental->customer->name }}</x-link-if>
                                <div class="text-muted fs-12 tabular-nums">{{ $rental->customer->phone }}</div>
                            </td>
                            <td class="text-wrap" style="min-width: 10rem; max-width: 16rem">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($rental->openItems as $item)
                                        <x-unit-chip :code="$item->unit->code" />
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-muted tabular-nums">{{ $rental->start_date->translatedFormat('j M') }}</td>
                            <td>
                                <div class="fw-medium tabular-nums">{{ $rental->due_date->translatedFormat('j M') }}</div>
                                <div class="fs-12 tabular-nums {{ $timing->status->textClass() }}">{{ $timing->label }}</div>
                            </td>
                            <td><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                            @if ($canViewRentals)
                                <td class="text-end">
                                    <a href="{{ route('rentals.show', $rental) }}" class="btn btn-ghost-secondary btn-icon btn-sm" aria-label="Detail penyewaan {{ $rental->customer->name }}" title="Detail">
                                        <x-icon name="chevron-right" class="fs-18" />
                                    </a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canViewRentals ? 6 : 5 }}"><x-empty-state>Tidak ada yang sedang disewa.</x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile list --}}
    <ul class="list-group list-group-flush d-lg-none">
        @forelse ($rows as ['rental' => $rental, 'timing' => $timing])
            @php($overdue = $timing->status === \App\Enums\RentalStatus::Overdue)
            <li wire:key="out-card-{{ $rental->id }}" @class(['list-group-item p-0', 'list-overdue' => $overdue])>
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
                            <div class="text-muted fs-12 tabular-nums">{{ $rental->customer->phone }}</div>
                        </div>
                        <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($rental->openItems as $item)
                            <x-unit-chip :code="$item->unit->code" />
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 fs-13">
                        <x-icon name="calendar-days" class="text-muted" />
                        <span class="tabular-nums">{{ $rental->start_date->translatedFormat('j M') }} → {{ $rental->due_date->translatedFormat('j M') }}</span>
                        <span @class([$timing->status->textClass(), 'fw-medium' => in_array($timing->status, [\App\Enums\RentalStatus::Overdue, \App\Enums\RentalStatus::Soon], true)])>· {{ $timing->label }}</span>
                    </div>
                </x-link-if>
            </li>
        @empty
            <li class="list-group-item"><x-empty-state>Tidak ada yang sedang disewa.</x-empty-state></li>
        @endforelse
    </ul>

    @if ($rentals->total() > 0)
        <div class="card-footer">{{ $rentals->links() }}</div>
    @endif
</div>
