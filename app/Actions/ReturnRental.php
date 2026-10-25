<?php

namespace App\Actions;

use App\Enums\UnitCondition;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnRental
{
    /**
     * Mark every unit still out on the rental as returned. Already-returned rentals are a no-op.
     *
     * Each returned item records the condition the unit came back in, and the unit takes that
     * condition. A unit missing from $conditions keeps the condition it already had.
     *
     * @param  array<int|string, string>  $conditions  UnitCondition values keyed by rental item id.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Rental $rental, array $conditions = []): void
    {
        $parsed = [];

        foreach ($conditions as $itemId => $value) {
            $parsed[(int) $itemId] = UnitCondition::tryFrom((string) $value)
                ?? throw ValidationException::withMessages(['conditions' => 'Kondisi unit tidak dikenal.']);
        }

        DB::transaction(function () use ($user, $rental, $parsed): void {
            $rental->openItems()->with('unit')->lockForUpdate()->get()->each(function (RentalItem $item) use ($user, $parsed): void {
                $condition = $parsed[$item->id] ?? $item->unit->condition;

                $item->forceFill([
                    'returned_at' => now(),
                    'return_condition' => $condition,
                    'updated_by' => $user->id,
                ])->save();

                if ($item->unit->condition !== $condition) {
                    $item->unit->forceFill(['condition' => $condition, 'updated_by' => $user->id])->save();
                }
            });
        });
    }
}
