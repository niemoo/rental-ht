<?php

namespace App\Http\Controllers;

use App\Support\Reports\Report;
use App\Support\Reports\Reports;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Print view and CSV / Excel downloads of a report, for the same period and filters as the report page
 * (read from the query string under the same names the page keeps in its URL).
 */
class ReportExportController extends Controller
{
    /**
     * One printable A4 page (landscape for wide tables): the browser's print dialog saves it as PDF.
     */
    public function print(Request $request, string $report): View
    {
        $built = Reports::make($report, $request->query());

        return view('reports.print', ['report' => $built]);
    }

    /**
     * CSV holds just the table, separated by semicolons so Excel with Indonesian settings splits the
     * columns. Excel gets the table on a "Data" sheet and the headline figures on a "Ringkasan" sheet.
     */
    public function download(Request $request, string $report, string $format): StreamedResponse
    {
        $built = Reports::make($report, $request->query());

        return response()->streamDownload(
            fn () => $format === 'csv' ? $this->writeCsv($built) : $this->writeXlsx($built),
            $built->filename().'.'.$format,
            ['Content-Type' => $format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function writeCsv(Report $report): void
    {
        $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: ';'));
        $writer->openToFile('php://output');
        $writer->addRow(Row::fromValues($report->headings()));

        foreach ($report->rows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();
    }

    private function writeXlsx(Report $report): void
    {
        $bold = new Style(fontBold: true);
        $writer = new XlsxWriter;
        $writer->openToFile('php://output');

        $writer->getCurrentSheet()->setName('Data');
        $writer->addRow(Row::fromValuesWithStyle($report->headings(), $bold));

        foreach ($report->rows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Ringkasan');
        $writer->addRow(Row::fromValuesWithStyle([$report->title()], new Style(fontBold: true, fontSize: 14)));
        $writer->addRow(Row::fromValues([$report->subtitle()]));
        $writer->addRow(Row::fromValues([]));

        foreach ($report->summary() as $label => $value) {
            $writer->addRow(Row::fromValues([$label, $value]));
        }

        $writer->close();
    }
}
