@props(['active', 'report', 'periodQuery', 'filterQuery' => [], 'period' => null, 'from' => '', 'to' => ''])

{{--
    Top of every report page: tabs between reports (keeping the period), the period picker (when $period
    is given — the Piutang report has none) and the print / CSV / Excel buttons for what is on screen.
    $periodQuery: ReportPeriod::query(); $filterQuery: the report's own filters as URL values.
--}}
@php
    $exportQuery = [...$periodQuery, ...$filterQuery];
@endphp
<x-page-header title="Laporan" :subtitle="$report->title().' · '.$report->subtitle()">
    <x-slot:action>
        <x-button variant="outline" :href="route('reports.print', ['report' => $active, ...$exportQuery])" target="_blank"><x-icon name="printer" />Cetak</x-button>
        <x-button variant="outline" :href="route('reports.download', ['report' => $active, 'format' => 'csv', ...$exportQuery])"><x-icon name="download" />CSV</x-button>
        <x-button variant="outline" :href="route('reports.download', ['report' => $active, 'format' => 'xlsx', ...$exportQuery])"><x-icon name="download" />Excel</x-button>
    </x-slot:action>
</x-page-header>

<div class="card mb-0">
    <div class="card-body pb-0">
        <ul class="nav nav-tabs nav-tabs-custom nav-success flex-nowrap overflow-x-auto mb-0" aria-label="Jenis laporan">
            @foreach (\App\Support\Reports\Reports::NAMES as $slug => $label)
                <li class="nav-item">
                    <a
                        href="{{ route('reports.'.$slug, $periodQuery) }}"
                        @class(['nav-link py-3 text-nowrap', 'active' => $slug === $active])
                        @if ($slug === $active) aria-current="page" @endif
                    >{{ $label }}</a>
                </li>
            @endforeach
        </ul>
    </div>

    @if ($period !== null)
        <div class="card-body border-top d-flex flex-wrap align-items-center gap-2">
            <ul class="nav nav-pills nav-custom-light gap-1" aria-label="Periode">
                @foreach (\App\Support\ReportPeriod::PRESETS as $preset => $label)
                    <li class="nav-item" wire:key="preset-{{ $preset }}">
                        <button
                            type="button"
                            wire:click="$set('period', '{{ $preset }}')"
                            @class(['nav-link py-1 px-3 fs-13 text-nowrap', 'active' => $period === $preset])
                            @if ($period === $preset) aria-pressed="true" @endif
                        >{{ $label }}</button>
                    </li>
                @endforeach
            </ul>
            @if ($period === 'custom')
                <div class="d-flex align-items-center gap-2">
                    <x-input type="date" wire:model.live="from" aria-label="Dari tanggal" class="form-control-sm w-auto tabular-nums" />
                    <span class="text-muted fs-13">s.d.</span>
                    <x-input type="date" wire:model.live="to" aria-label="Sampai tanggal" class="form-control-sm w-auto tabular-nums" />
                </div>
            @endif
        </div>
    @endif
</div>
