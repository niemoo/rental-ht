<?php

namespace App\Actions;

use App\Actions\Concerns\ResolvesCustomer;
use App\Enums\UnitCondition;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRental
{
    use ResolvesCustomer;

    /**
     * Change the customer, dates, units or event details of a rental that isn't completed yet.
     *
     * Units already returned stay on the rental as they are; unit_ids lists the units that should
     * still be out or booked. Like {@see CreateRental}, the unit rows are locked so a concurrent
     * booking of the same unit is serialized.
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
     *     Units added are booked at their category's current daily rate; units kept keep theirs.
     *     Leave out discount to keep the current one.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Rental $rental, array $data): Rental
    {
        $start = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $due = CarbonImmutable::parse($data['due_date'])->endOfDay();
        $unitIds = array_values(array_unique($data['unit_ids']));

        if ($due->lt($start)) {
            throw ValidationException::withMessages(['due_date' => 'Tanggal kembali tidak boleh sebelum tanggal mulai.']);
        }

        return DB::transaction(function () use ($user, $rental, $data, $start, $due, $unitIds): Rental {
            $openItems = $rental->openItems()->lockForUpdate()->get();
            $returnedCount = $rental->items()->whereNotNull('returned_at')->count();

            if ($openItems->isEmpty()) {
                throw ValidationException::withMessages(['unit_ids' => 'Penyewaan ini sudah selesai, jadi tidak bisa diubah lagi.']);
            }

            if ($unitIds === [] && $returnedCount === 0) {
                throw ValidationException::withMessages(['unit_ids' => 'Pilih minimal satu unit']);
            }

            $bookedUnitIds = $openItems->pluck('unit_id')->all();
            $units = Unit::query()->whereKey($unitIds)->with('category')->orderBy('id')->lockForUpdate()->get();

            if ($units->count() !== count($unitIds)) {
                throw ValidationException::withMessages(['unit_ids' => 'Satu atau beberapa unit sudah tidak ada.']);
            }

            // Units already on this rental may stay even if their condition changed; new ones must be usable.
            $unavailable = Unit::query()
                ->whereKey($unitIds)
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query->where('units.condition', '!=', UnitCondition::Good->value)->whereKeyNot($bookedUnitIds))
                    ->orWhereNot(fn (Builder $query) => $query->freeBetween($start, $due, $rental)))
                ->orderBy('code')
                ->pluck('code');

            if ($unavailable->isNotEmpty()) {
                throw ValidationException::withMessages(['unit_ids' => 'Unit ini tidak tersedia: '.$unavailable->implode(', ')]);
            }

            $rental->update([
                'customer_id' => (int) ($data['customer_id'] ?? 0) === $rental->customer_id
                    ? $rental->customer_id
                    : $this->resolveCustomer($user, $data)->id,
                'start_date' => $start,
                'due_date' => $due,
                'event_name' => self::nullIfBlank($data['event_name'] ?? null),
                'event_address' => self::nullIfBlank($data['event_address'] ?? null),
                'event_social' => self::nullIfBlank($data['event_social'] ?? null),
                ...(array_key_exists('discount', $data) ? self::discountAttributes($data['discount']) : []),
            ]);

            $openItems
                ->reject(fn (RentalItem $item): bool => in_array($item->unit_id, $unitIds, true))
                ->each(fn (RentalItem $item) => $item->delete());

            $rental->items()->createMany(
                $units
                    ->reject(fn (Unit $unit): bool => in_array($unit->id, $bookedUnitIds, true))
                    ->map(fn (Unit $unit): array => ['unit_id' => $unit->id, 'daily_rate' => $unit->category->daily_rate, 'created_by' => $user->id])
                    ->values()
                    ->all(),
            );

            $rental->syncTotals();

            return $rental;
        });
    }
}
