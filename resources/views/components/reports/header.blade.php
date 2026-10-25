@props(['active', 'report', 'periodQuery', 'filterQuery' => [], 'period' => null, 'from' => '', 'to' => ''])

{{--
    Top of every report page: tabs between reports (keeping the period), the period picker (when $period
    is given — the Piutang report has none) and the print / CSV / Excel buttons for what is on screen.
    $periodQuery: ReportPeriod::query(); $filterQuery: the report's own filters as URL values.
--}}
@php($exportQuery = [...$periodQuery, ...$filterQuery])
<div class="flex flex-col gap-4">
    <x-page-header title="Laporan" :subtitle="$report->title().' · '.$report->subtitle()">
        <x-slot:action>
            <div class="flex flex-wrap gap-2">
                <x-button variant="outline" :href="route('reports.print', ['report' => $active, ...$exportQuery])" target="_blank"><x-icon name="printer" /> Cetak</x-button>
                <x-button variant="outline" :href="route('reports.download', ['report' => $active, 'format' => 'csv', ...$exportQuery])"><x-icon name="download" /> CSV</x-button>
                <x-button variant="outline" :href="route('reports.download', ['report' => $active, 'format' => 'xlsx', ...$exportQuery])"><x-icon name="download" /> Excel</x-button>
            </div>
        </x-slot:action>
    </x-page-header>

    <nav class="flex gap-1 overflow-x-auto border-b border-border" aria-label="Jenis laporan">
        @foreach (\App\Support\Reports\Reports::NAMES as $slug => $label)
            <a
                href="{{ route('reports.'.$slug, $periodQuery) }}"
                @class([
                    '-mb-px border-b-2 px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                    'border-primary text-primary' => $slug === $active,
                    'border-transparent text-muted-foreground hover:text-foreground' => $slug !== $active,
                ])
                @if ($slug === $active) aria-current="page" @endif
            >{{ $label }}</a>
        @endforeach
    </nav>

    @if ($period !== null)
        <div class="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="inline-flex w-fit max-w-full gap-1 overflow-x-auto rounded-lg border border-border bg-card p-1 shadow-xs">
                @foreach (\App\Support\ReportPeriod::PRESETS as $preset => $label)
                    <button
                        type="button"
                        wire:click="$set('period', '{{ $preset }}')"
                        wire:key="preset-{{ $preset }}"
                        @class([
                            'rounded-md px-3 py-1.5 text-[13px] font-medium whitespace-nowrap transition-colors',
                            'bg-primary text-primary-foreground' => $period === $preset,
                            'text-muted-foreground hover:text-foreground' => $period !== $preset,
                        ])
                    >{{ $label }}</button>
                @endforeach
            </div>
            @if ($period === 'custom')
                <div class="flex items-center gap-2">
                    <x-input type="date" wire:model.live="from" aria-label="Dari tanggal" class="w-auto tabular-nums" />
                    <span class="text-sm text-muted-foreground">s.d.</span>
                    <x-input type="date" wire:model.live="to" aria-label="Sampai tanggal" class="w-auto tabular-nums" />
                </div>
            @endif
        </div>
    @endif
</div>
