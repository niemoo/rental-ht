<?php

namespace App\Livewire\Rentals;

use App\Actions\CancelRental;
use App\Enums\Permission;
use App\Models\Rental;
use App\Support\RentalBill;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Cancel a rental that hasn't started yet (rentals.cancel), with a reason and an optional cancellation fee.
 */
class Cancel extends Component
{
    public Rental $rental;

    public string $reason = '';

    /** Whole rupiah, as typed. */
    public string $fee = '0';

    public function mount(Rental $rental): void
    {
        $this->rental = $rental;

        if (! $rental->isCancellable()) {
            session()->flash('status', 'Hanya penyewaan yang belum dimulai yang bisa dibatalkan.');
            $this->redirectRoute('rentals.show', $rental);
        }
    }

    public function save(CancelRental $cancelRental): void
    {
        $this->authorize(Permission::CancelRentals);

        $validated = $this->validate(
            [
                'reason' => ['required', 'string', 'max:255'],
                'fee' => ['required', 'integer', 'min:0', 'max:1000000000'],
            ],
            [
                'reason.required' => 'Alasan pembatalan wajib diisi',
                'fee.required' => 'Isi 0 kalau tidak ada biaya pembatalan.',
                'fee.integer' => 'Biaya harus angka bulat dalam rupiah.',
            ],
        );

        $rental = $cancelRental->handle(Auth::user(), $this->rental, ['reason' => $validated['reason'], 'fee' => (int) $validated['fee']]);
        $bill = $rental->bill();

        session()->flash('status', $bill->balance() < 0
            ? 'Penyewaan dibatalkan. Ada kelebihan bayar '.RentalBill::rupiah(-$bill->balance()).' yang perlu dikembalikan.'
            : 'Penyewaan dibatalkan.');

        $this->redirectRoute('rentals.show', $rental);
    }

    public function render(): View
    {
        return view('livewire.rentals.cancel', ['bill' => $this->rental->bill()])->title('Batalkan penyewaan');
    }
}
