<?php

namespace App\Livewire\Payments;

use App\Actions\RecordPayment;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Models\Rental;
use App\Support\RentalBill;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Record a payment for a rental (payments.record), or a refund when the customer has overpaid.
 */
class Form extends Component
{
    public Rental $rental;

    /** "payment", or "refund" when giving money back. */
    public string $kind = 'payment';

    /** Whole rupiah, as typed. Always positive; a refund is stored negative. */
    public string $amount = '';

    public string $paidAt = '';

    /** A PaymentMethod value. */
    public string $method = 'cash';

    public string $note = '';

    /**
     * Start with what is still owed, or with the overpaid amount as a refund.
     */
    public function mount(Rental $rental): void
    {
        $this->rental = $rental;
        $this->paidAt = today()->toDateString();

        $balance = $this->bill->balance();

        if ($balance < 0) {
            $this->kind = 'refund';
            $this->amount = (string) -$balance;
        } elseif ($balance > 0) {
            $this->amount = (string) $balance;
        }
    }

    public function save(RecordPayment $recordPayment): void
    {
        $this->authorize(Permission::RecordPayments);

        $validated = $this->validate(
            [
                'kind' => ['required', Rule::in(['payment', 'refund'])],
                'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
                'paidAt' => ['required', 'date', 'before_or_equal:today'],
                'method' => ['required', Rule::enum(PaymentMethod::class)],
                'note' => ['nullable', 'string', 'max:255'],
            ],
            [
                'amount.required' => 'Jumlah wajib diisi',
                'amount.integer' => 'Jumlah harus angka bulat dalam rupiah.',
                'amount.min' => 'Jumlah harus lebih dari 0.',
                'paidAt.required' => 'Pilih tanggal bayar',
                'paidAt.before_or_equal' => 'Tanggal bayar tidak boleh di masa depan.',
            ],
        );

        $payment = $recordPayment->handle(Auth::user(), $this->rental, [
            'amount' => (int) $validated['amount'],
            'paid_at' => $validated['paidAt'],
            'method' => $validated['method'],
            'note' => $validated['note'],
            'is_refund' => $validated['kind'] === 'refund',
        ]);

        session()->flash('status', ($payment->isRefund() ? 'Pengembalian dana ' : 'Pembayaran ').RentalBill::rupiah(abs($payment->amount)).' dicatat.');

        $this->redirectRoute('rentals.show', $this->rental);
    }

    #[Computed]
    public function bill(): RentalBill
    {
        return $this->rental->bill();
    }

    public function render(): View
    {
        return view('livewire.payments.form')->title('Catat pembayaran');
    }
}
