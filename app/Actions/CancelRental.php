<?php

namespace App\Actions;

use App\Models\Rental;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelRental
{
    /**
     * Cancel a rental that hasn't started yet.
     *
     * Its units are closed at once (returned_at is set to now, with no return condition, since they never
     * went out), so they are free to book again and the rental drops out of every "open" list. The bill
     * becomes the cancellation fee; any payment above it is shown as overpaid, to be refunded.
     *
     * @param  array{reason: string, fee: int}  $data  Validated input.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Rental $rental, array $data): Rental
    {
        return DB::transaction(function () use ($user, $rental, $data): Rental {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);

            if (! $rental->isCancellable()) {
                throw ValidationException::withMessages(['reason' => 'Hanya penyewaan yang belum dimulai yang bisa dibatalkan.']);
            }

            $rental->forceFill([
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => trim($data['reason']),
                'cancellation_fee' => $data['fee'],
            ])->save();

            $rental->openItems()->update(['returned_at' => now(), 'updated_by' => $user->id]);

            $rental->syncTotals();

            return $rental;
        });
    }
}
