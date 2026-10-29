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
<html lang="id" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $report->title() }} · {{ $report->subtitle() }}</title>
        @include('partials.favicon')
        @fonts
        @include('partials.velzon-styles')
        @vite(['resources/css/app.css'])
        <style>
            @page { size: A4 landscape; margin: 12mm; }

            body { background: #f3f3f9; }
            .report-sheet { max-width: 297mm; margin: 0 auto; padding: 12mm; background: #fff; box-shadow: 0 1rem 2.5rem rgba(56, 65, 74, 0.12); }
            .report-sheet .table > :not(caption) > * > * { padding: 0.35rem 0.5rem; }
            .report-sheet tbody tr { break-inside: avoid; }
            .report-label { font-size: 0.6875rem; letter-spacing: 0.06em; }
            .report-rule-bottom, .report-rule-bottom > th { border-bottom: 2px solid #212529; }
            .report-rule-top > td { border-top: 2px solid #212529; border-bottom: 0; }

            @media print {
                body { background: #fff; padding: 0 !important; }
                .report-sheet { max-width: none; padding: 0; box-shadow: none; }
            }
        </style>
    </head>
    <body class="py-4">
        <div class="d-flex justify-content-end mx-auto mb-3 px-3 d-print-none" style="max-width: 297mm">
            <button type="button" onclick="window.print()" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <x-icon name="printer" /> Cetak / PDF
            </button>
        </div>

        <main class="report-sheet">
            <header class="report-rule-bottom d-flex align-items-start justify-content-between gap-4 pb-3">
                <div>
                    <h1 class="fs-22 fw-bold mb-1">{{ $report->title() }}</h1>
                    <p class="text-muted fw-medium mb-0">{{ $report->subtitle() }}</p>
                </div>
                <div class="text-end">
                    <img src="{{ asset('images/krc-logo.png') }}" alt="KRC — {{ config('invoice.business.name') }}" style="height: 3rem; width: auto">
                    <p class="text-muted fs-11 mb-0 mt-1">Dicetak {{ now()->translatedFormat('j M Y H:i') }}</p>
                </div>
            </header>

            <section class="row g-3 mt-2">
                @foreach ($report->summary() as $label => $value)
                    <div class="col-3">
                        <div class="report-label text-uppercase fw-semibold text-muted">{{ $label }}</div>
                        <div class="fw-semibold tabular-nums">{{ $value }}</div>
                    </div>
                @endforeach
            </section>

            <table class="table table-sm fs-12 mt-4 mb-0">
                <thead>
                    <tr class="report-rule-bottom">
                        @foreach ($report->headings() as $column => $heading)
                            <th @class(['report-label text-uppercase text-muted fw-semibold', 'text-end' => in_array($column, $moneyColumns, true)])>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="align-top">
                            @foreach ($row as $column => $value)
                                @if (in_array($column, $moneyColumns, true))
                                    <td class="text-end text-nowrap tabular-nums">{{ RentalBill::rupiah((int) $value) }}</td>
                                @elseif (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                                    <td class="text-nowrap tabular-nums">{{ \Carbon\Carbon::parse($value)->translatedFormat('j M Y') }}</td>
                                @elseif (is_int($value))
                                    <td class="tabular-nums">{{ Number::format($value) }}</td>
                                @else
                                    <td>{{ $value ?? '—' }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report->headings()) }}" class="py-4 text-center text-muted">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows !== [])
                    <tfoot>
                        <tr class="report-rule-top fw-semibold">
                            @foreach ($report->headings() as $column => $heading)
                                @if ($loop->first)
                                    <td class="text-nowrap">Total · {{ Number::format(count($rows)) }} baris</td>
                                @elseif (array_key_exists($column, $totals))
                                    <td class="text-end text-nowrap tabular-nums">{{ RentalBill::rupiah($totals[$column]) }}</td>
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
