<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\Contracts\View\View;

/**
 * Printable invoice for one rental (one A4 page; the browser's print dialog saves it as PDF).
 */
class RentalInvoiceController extends Controller
{
    public function __invoke(Rental $rental): View
    {
        $rental->load(['customer', 'charges', 'payments' => fn ($query) => $query->orderBy('paid_at')->orderBy('id')]);

        return view('rentals.invoice', [
            'rental' => $rental,
            'bill' => $rental->bill(),
        ]);
    }
}
