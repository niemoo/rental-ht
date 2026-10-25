<?php

namespace App\Livewire\Charges;

use App\Enums\ChargeType;
use App\Enums\Permission;
use App\Models\Rental;
use App\Support\RentalBill;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Add an extra charge to a rental (rentals.charges): late fee, damage, loss, delivery or anything else.
 * Picking "Denda telat" fills in a suggested amount from the units' rates and days late.
 */
class Form extends Component
{
    public Rental $rental;

    /** A ChargeType value. */
    public string $type = '';

    public string $description = '';

    /** Whole rupiah, as typed. */
    public string $amount = '';

    public function mount(Rental $rental): void
    {
        $this->rental = $rental;

        if ($rental->isCancelled()) {
            session()->flash('status', 'Penyewaan yang dibatalkan tidak bisa diberi biaya tambahan.');
            $this->redirectRoute('rentals.show', $rental);

            return;
        }

        $this->type = $this->lateFee['amount'] > 0 ? ChargeType::LateFee->value : ChargeType::Delivery->value;
        $this->amount = $this->lateFee['amount'] > 0 ? (string) $this->lateFee['amount'] : '';
    }

    /**
     * Switching to "Denda telat" with no amount typed yet fills in the suggestion.
     */
    public function updatedType(string $type): void
    {
        if ($type === ChargeType::LateFee->value && blank($this->amount) && $this->lateFee['amount'] > 0) {
            $this->amount = (string) $this->lateFee['amount'];
        }
    }

    public function save(): void
    {
        $this->authorize(Permission::ManageCharges);
        abort_if($this->rental->isCancelled(), 403);

        $validated = $this->validate(
            [
                'type' => ['required', Rule::enum(ChargeType::class)],
                'description' => ['nullable', 'string', 'max:255'],
                'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            ],
            [
                'amount.required' => 'Jumlah wajib diisi',
                'amount.integer' => 'Jumlah harus angka bulat dalam rupiah.',
                'amount.min' => 'Jumlah harus lebih dari 0.',
            ],
        );

        $this->rental->charges()->create([
            'type' => ChargeType::from($validated['type']),
            'description' => filled($validated['description']) ? trim($validated['description']) : null,
            'amount' => (int) $validated['amount'],
            'created_by' => Auth::id(),
        ]);

        $this->rental->syncTotals();

        session()->flash('status', ChargeType::from($validated['type'])->label().' '.RentalBill::rupiah((int) $validated['amount']).' ditambahkan.');

        $this->redirectRoute('rentals.show', $this->rental);
    }

    /**
     * @return array{days: int, amount: int}
     */
    #[Computed]
    public function lateFee(): array
    {
        return RentalBill::suggestedLateFee($this->rental);
    }

    public function render(): View
    {
        return view('livewire.charges.form')->title('Tambah biaya');
    }
}
