<?php

namespace App\Support\Reports;

/**
 * What a report gives its print view and its CSV / Excel exports, so all three show the same figures
 * as the report page.
 */
interface Report
{
    public function title(): string;

    /**
     * The period or moment the report covers, e.g. "1 Okt – 31 Okt 2026" or "Per 25 Okt 2026".
     */
    public function subtitle(): string;

    /**
     * File name for downloads, without extension, e.g. "laporan-pendapatan-2026-10-01-2026-10-31".
     */
    public function filename(): string;

    /**
     * Headline figures, already formatted, as [label => value].
     *
     * @return array<string, string>
     */
    public function summary(): array;

    /**
     * @return list<string>
     */
    public function headings(): array;

    /**
     * Positions in {@see self::headings()} of columns holding rupiah amounts, so the print view can format them.
     *
     * @return list<int>
     */
    public function moneyColumns(): array;

    /**
     * Every row of the detail table, unpaginated, in the order of {@see self::headings()}.
     * Money is whole rupiah as int and dates are Y-m-d strings, so spreadsheets can add them up and sort them.
     *
     * @return iterable<list<string|int|null>>
     */
    public function rows(): iterable;
}
