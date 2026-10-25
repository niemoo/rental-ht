<?php

namespace App\Livewire\Rentals;

use App\Enums\Permission;
use App\Livewire\Concerns\ReturnsRentals;
use App\Models\Rental;
use App\Models\Unit;
use App\Support\RentalBill;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    use ReturnsRentals;

    public Rental $rental;

    /** Feedback shown in the bill section after deleting a payment or charge. */
    public ?string $notice = null;

    /**
     * Soft-delete a payment entered by mistake; who deleted it is recorded.
     */
    public function deletePayment(int $paymentId): void
    {
        $this->authorize(Permission::DeletePayments);

        $payment = $this->rental->payments()->findOrFail($paymentId);
        $payment->delete();
        $this->rental->syncTotals();

        $this->notice = ($payment->isRefund() ? 'Pengembalian dana ' : 'Pembayaran ').RentalBill::rupiah(abs($payment->amount)).' dihapus.';
    }

    public function deleteCharge(int $chargeId): void
    {
        $this->authorize(Permission::ManageCharges);

        $charge = $this->rental->charges()->findOrFail($chargeId);
        $charge->delete();
        $this->rental->syncTotals();

        $this->notice = $charge->type->label().' '.RentalBill::rupiah($charge->amount).' dihapus.';
    }

    public function render(): View
    {
        $this->rental->unsetRelations()->load([
            'customer',
            'creator:id,name',
            'canceller:id,name',
            'items' => fn ($query) => $query->with(['unit.category', 'updater:id,name'])->orderBy(
                Unit::select('code')->whereColumn('units.id', 'rental_items.unit_id'),
            ),
            'charges' => fn ($query) => $query->with('creator:id,name')->orderBy('id'),
            'payments' => fn ($query) => $query->with('creator:id,name')->orderBy('paid_at')->orderBy('id'),
        ]);

        return view('livewire.rentals.show', [
            'timing' => $this->rental->timing(),
            'bill' => $this->rental->bill(),
        ])->title($this->rental->customer->name);
    }
}
