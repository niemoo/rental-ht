<?php

namespace App\Actions;

use App\Actions\Concerns\ResolvesCustomer;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateRental
{
    use ResolvesCustomer;

    /**
     * Book units for a customer over a date range.
     *
     * The chosen unit rows are locked for the duration of the transaction, so two staff members
     * booking the same unit at the same moment are serialized and the second one sees the conflict.
     *
     * @param  array{
     *     customer_id?: int|null,
     *     customer_name?: string|null,
     *     customer_phone?: string|null,
     *     unit_ids: list<int>,
     *     start_date: string,
     *     due_date: string,
     *     event_name?: string|null,
     *     event_address?: string|null,
     *     event_social?: string|null,
     *     discount?: array{type: string|null, value: int, note: string|null},
     * }  $data  Validated input. Pass customer_id for an existing customer, or name + phone for a new one.
     *     Each unit is booked at its category's current daily rate. Leave out discount for none.
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $data): Rental
    {
        $start = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $due = CarbonImmutable::parse($data['due_date'])->endOfDay();
        $unitIds = array_values(array_unique($data['unit_ids']));

        if ($due->lt($start)) {
            throw ValidationException::withMessages(['due_date' => 'Tanggal kembali tidak boleh sebelum tanggal mulai.']);
        }

        return DB::transaction(function () use ($user, $data, $start, $due, $unitIds): Rental {
            $units = Unit::query()->whereKey($unitIds)->with('category')->orderBy('id')->lockForUpdate()->get();

            if ($units->count() !== count($unitIds)) {
                throw ValidationException::withMessages(['unit_ids' => 'Satu atau beberapa unit sudah tidak ada.']);
            }

            $unavailable = Unit::query()->whereKey($unitIds)->unavailableBetween($start, $due)->orderBy('code')->pluck('code');

            if ($unavailable->isNotEmpty()) {
                throw ValidationException::withMessages(['unit_ids' => 'Unit ini tidak tersedia: '.$unavailable->implode(', ')]);
            }

            $rental = Rental::create([
                'customer_id' => $this->resolveCustomer($user, $data)->id,
                'start_date' => $start,
                'due_date' => $due,
                'event_name' => self::nullIfBlank($data['event_name'] ?? null),
                'event_address' => self::nullIfBlank($data['event_address'] ?? null),
                'event_social' => self::nullIfBlank($data['event_social'] ?? null),
                ...self::discountAttributes($data['discount'] ?? null),
                'created_by' => $user->id,
            ]);

            $rental->items()->createMany(
                $units->map(fn (Unit $unit): array => ['unit_id' => $unit->id, 'daily_rate' => $unit->category->daily_rate, 'created_by' => $user->id])->all(),
            );

            $rental->syncTotals();

            return $rental;
        });
    }
}
