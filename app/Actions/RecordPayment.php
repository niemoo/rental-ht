<?php

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPayment
{
    /**
     * Record money received for a rental, or a refund paid back to the customer.
     *
     * A refund is stored as a negative amount and may not exceed what the customer overpaid.
     *
     * @param  array{amount: int, paid_at: string, method: string, note?: string|null, is_refund?: bool}  $data  Validated input; amount is always positive.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Rental $rental, array $data): RentalPayment
    {
        return DB::transaction(function () use ($user, $rental, $data): RentalPayment {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);
            $isRefund = (bool) ($data['is_refund'] ?? false);

            if ($isRefund && $data['amount'] > $rental->bill()->paid - $rental->bill()->total) {
                throw ValidationException::withMessages(['amount' => 'Pengembalian dana tidak boleh lebih dari kelebihan bayar.']);
            }

            $payment = $rental->payments()->create([
                'amount' => $isRefund ? -$data['amount'] : $data['amount'],
                'paid_at' => $data['paid_at'],
                'method' => PaymentMethod::from($data['method']),
                'note' => filled($data['note'] ?? null) ? trim($data['note']) : null,
                'created_by' => $user->id,
            ]);

            $rental->syncTotals();

            return $payment;
        });
    }
}
