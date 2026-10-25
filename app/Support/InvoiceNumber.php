<?php

namespace App\Support;

use App\Models\Rental;
use Carbon\CarbonInterface;

/**
 * Invoice numbers like INV/2026/10/0042: numbered from 1 again each month.
 */
final class InvoiceNumber
{
    /**
     * The next free number for the month of $date. Call inside a transaction: the latest number is
     * read with a lock, and the unique index on rentals.invoice_number catches anything that slips past.
     */
    public static function next(CarbonInterface $date): string
    {
        $prefix = 'INV/'.$date->format('Y/m').'/';

        $last = Rental::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->value('invoice_number');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
