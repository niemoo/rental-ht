{{--
    Printable report: headline figures, then the whole detail table (not paginated). Always light, like paper;
    A4 landscape so wide tables fit. Uses the same Report object as the CSV / Excel downloads.
--}}
@php
    use App\Support\RentalBill;

    $moneyColumns = $report->moneyColumns();
    $rows = iterator_to_array($report->rows(), false);
    $totals = collect($moneyColumns)->mapWithKeys(fn (int $column): array => [$column => array_sum(array_column($rows, $column))])->all();
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $report->title() }} · {{ $report->subtitle() }}</title>
        @fonts
        @vite(['resources/css/app.css'])
        <style>
            @page { size: A4 landscape; margin: 12mm; }
        </style>
    </head>
    <body class="min-h-screen bg-slate-100 py-8 text-slate-800 antialiased print:bg-white print:py-0">
        <div class="mx-auto mb-4 flex max-w-[297mm] items-center justify-end px-4 print:hidden">
            <button type="button" onclick="window.print()" class="inline-flex h-9 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white shadow-sm hover:bg-primary/90">
                <x-icon name="printer" class="size-4" /> Cetak / PDF
            </button>
        </div>

        <main class="mx-auto max-w-[297mm] bg-white px-[12mm] py-10 shadow-xl shadow-slate-300/50 print:p-0 print:shadow-none">
            <header class="flex items-start justify-between gap-8 border-b-2 border-slate-900 pb-5">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $report->title() }}</h1>
                    <p class="mt-1 text-sm font-medium text-slate-500">{{ $report->subtitle() }}</p>
                </div>
                <div class="text-right">
                    <img src="{{ asset('images/krc-logo.png') }}" alt="KRC — {{ config('invoice.business.name') }}" class="ml-auto h-12 w-auto">
                    <p class="mt-1 text-[11px] text-slate-400">Dicetak {{ now()->translatedFormat('j M Y H:i') }}</p>
                </div>
            </header>

            <section class="mt-6 grid grid-cols-4 gap-x-6 gap-y-3 text-sm">
                @foreach ($report->summary() as $label => $value)
                    <div>
                        <div class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $label }}</div>
                        <div class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ $value }}</div>
                    </div>
                @endforeach
            </section>

            <table class="mt-8 w-full text-xs">
                <thead>
                    <tr class="border-b-2 border-slate-900 text-left text-[10px] font-semibold tracking-wider text-slate-500 uppercase">
                        @foreach ($report->headings() as $column => $heading)
                            <th @class(['pb-2 pr-3 last:pr-0', 'text-right' => in_array($column, $moneyColumns, true)])>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-b border-slate-200 align-top break-inside-avoid">
                            @foreach ($row as $column => $value)
                                @if (in_array($column, $moneyColumns, true))
                                    <td class="py-1.5 pr-3 text-right tabular-nums last:pr-0">{{ RentalBill::rupiah((int) $value) }}</td>
                                @elseif (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                                    <td class="py-1.5 pr-3 whitespace-nowrap tabular-nums last:pr-0">{{ \Carbon\Carbon::parse($value)->translatedFormat('j M Y') }}</td>
                                @else
                                    <td class="py-1.5 pr-3 last:pr-0">{{ $value ?? '—' }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report->headings()) }}" class="py-6 text-center text-slate-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows !== [])
                    <tfoot>
                        <tr class="border-t-2 border-slate-900 font-semibold text-slate-900">
                            @foreach ($report->headings() as $column => $heading)
                                @if ($loop->first)
                                    <td class="py-2 pr-3">Total · {{ count($rows) }} baris</td>
                                @elseif (array_key_exists($column, $totals))
                                    <td class="py-2 pr-3 text-right tabular-nums last:pr-0">{{ RentalBill::rupiah($totals[$column]) }}</td>
                                @else
                                    <td></td>
                                @endif
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </main>
    </body>
</html>
