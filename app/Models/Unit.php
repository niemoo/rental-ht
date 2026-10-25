<?php

namespace App\Models;

use App\Enums\UnitCondition;
use App\Models\Concerns\RecordsAuthor;
use Carbon\CarbonInterface;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category_id', 'code', 'condition', 'created_by'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'condition' => 'good',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition' => UnitCondition::class,
        ];
    }

    /**
     * Includes deleted categories, so a deleted unit in rental history still has one.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /**
     * A unit that is out or booked must be returned or taken off its rental before it can be deleted.
     */
    public function isDeletable(): bool
    {
        return $this->openRentalItems()->doesntExist();
    }

    /**
     * Remove the unit. One that was ever rented is soft-deleted so its rental history stays intact;
     * one that never was is deleted for good, which frees its code for reuse.
     */
    public function remove(): void
    {
        if ($this->rentalItems()->withTrashed()->exists()) {
            $this->delete();
        } else {
            $this->forceDelete();
        }
    }

    /**
     * @return HasMany<RentalItem, $this>
     */
    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    /**
     * Unreturned items on live rentals — the booking that is out now as well as future ones.
     *
     * @return HasMany<RentalItem, $this>
     */
    public function openRentalItems(): HasMany
    {
        return $this->rentalItems()->whereNull('returned_at')->whereHas('rental');
    }

    /**
     * Units physically out right now (an unreturned booking has already started).
     */
    #[Scope]
    protected function outNow(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->whereHas('openRentalItems.rental', fn (Builder $rental) => $rental->where('start_date', '<=', $now ?? now()));
    }

    /**
     * Units on the shelf right now (not {@see outNow()}), whatever their condition.
     */
    #[Scope]
    protected function onShelf(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->whereDoesntHave('openRentalItems.rental', fn (Builder $rental) => $rental->where('start_date', '<=', $now ?? now()));
    }

    /**
     * Units in good condition — the only ones that can be booked.
     */
    #[Scope]
    protected function usable(Builder $query): void
    {
        $query->where('units.condition', UnitCondition::Good->value);
    }

    /**
     * Units that could go out right now: on the shelf and in good condition (they may still have future bookings).
     */
    #[Scope]
    protected function availableNow(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->usable()->onShelf($now);
    }

    /**
     * Units on the shelf that can't be used: under repair, damaged or lost.
     */
    #[Scope]
    protected function outOfService(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->where('units.condition', '!=', UnitCondition::Good->value)->onShelf($now);
    }

    /**
     * Units still out past their due date.
     */
    #[Scope]
    protected function overdue(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->whereHas('openRentalItems.rental', fn (Builder $rental) => $rental->where('due_date', '<', $now ?? now()));
    }

    /**
     * Units out right now whose due date hasn't passed yet.
     */
    #[Scope]
    protected function outOnTime(Builder $query, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $query->outNow($now)->whereDoesntHave('openRentalItems.rental', fn (Builder $rental) => $rental->where('due_date', '<', $now));
    }

    /**
     * Units that cannot be booked for [$start, $due].
     *
     * A unit is blocked when it is not in good condition, by an unreturned booking that overlaps
     * the range, or by any unreturned booking that is already overdue: an overdue unit is physically
     * still out, so it stays blocked for every date until someone marks it returned.
     */
    #[Scope]
    protected function unavailableBetween(Builder $query, CarbonInterface $start, CarbonInterface $due): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('units.condition', '!=', UnitCondition::Good->value)
            ->orWhereHas('openRentalItems.rental', fn (Builder $rental) => self::constrainToBlocking($rental, $start, $due)));
    }

    /**
     * Inverse of {@see unavailableBetween()}.
     */
    #[Scope]
    protected function availableBetween(Builder $query, CarbonInterface $start, CarbonInterface $due): void
    {
        $query->usable()->freeBetween($start, $due);
    }

    /**
     * Units no unreturned booking blocks for [$start, $due], whatever their condition. Pass the rental
     * being edited as $ignoring so its own bookings don't count against it.
     */
    #[Scope]
    protected function freeBetween(Builder $query, CarbonInterface $start, CarbonInterface $due, ?Rental $ignoring = null): void
    {
        $query->whereDoesntHave('openRentalItems.rental', fn (Builder $rental) => self::constrainToBlocking($rental, $start, $due, $ignoring));
    }

    /**
     * @param  Builder<Rental>  $rental
     */
    private static function constrainToBlocking(Builder $rental, CarbonInterface $start, CarbonInterface $due, ?Rental $ignoring = null): void
    {
        $rental->when($ignoring, fn (Builder $query) => $query->whereKeyNot($ignoring->getKey()));
        $rental->where(function (Builder $query) use ($start, $due): void {
            $query->where(fn (Builder $overlap) => $overlap->where('start_date', '<=', $due)->where('due_date', '>=', $start))
                ->orWhere('due_date', '<', now());
        });
    }
}
