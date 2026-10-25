<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\RecordsAuthor;
use App\Support\InvoiceNumber;
use App\Support\RentalBill;
use App\Support\RentalTiming;
use Carbon\CarbonInterface;
use Database\Factories\RentalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'invoice_number', 'customer_id', 'start_date', 'due_date',
    'event_name', 'event_address', 'event_social',
    'discount_type', 'discount_value', 'discount_note', 'created_by',
])]
class Rental extends Model
{
    /** @use HasFactory<RentalFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /** Status filter options for rental lists, in the order {@see self::statusRankSql()} sorts them. */
    public const STATUSES = ['active' => 'Aktif', 'upcoming' => 'Mendatang', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];

    /**
     * Number every new rental's invoice in the month it is booked.
     */
    protected static function booted(): void
    {
        static::creating(function (self $rental): void {
            $rental->invoice_number ??= InvoiceNumber::next($rental->created_at ?? now());
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'due_date' => 'datetime',
            'items_max_returned_at' => 'datetime',
            'discount_type' => DiscountType::class,
            'discount_value' => 'integer',
            'total_amount' => 'integer',
            'paid_amount' => 'integer',
            'cancelled_at' => 'datetime',
            'cancellation_fee' => 'integer',
        ];
    }

    /**
     * Includes deleted customers, so past rentals still show who rented.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /**
     * @return HasMany<RentalItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    /**
     * @return HasMany<RentalItem, $this>
     */
    public function openItems(): HasMany
    {
        return $this->items()->whereNull('returned_at');
    }

    /**
     * @return HasMany<RentalCharge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(RentalCharge::class);
    }

    /**
     * @return HasMany<RentalPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(RentalPayment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Rentals with at least one unit not yet returned.
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereHas('openItems');
    }

    /**
     * Open rentals that have already started — their units are physically out.
     */
    #[Scope]
    protected function outNow(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->open()->where('start_date', '<=', $now ?? now());
    }

    /**
     * Open rentals that start in the future.
     */
    #[Scope]
    protected function upcoming(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->open()->where('start_date', '>', $now ?? now());
    }

    /**
     * Rentals that were not cancelled.
     */
    #[Scope]
    protected function notCancelled(Builder $query): void
    {
        $query->whereNull('rentals.cancelled_at');
    }

    /**
     * Cancelled rentals. Cancelling closes every item, so these never count as open.
     */
    #[Scope]
    protected function cancelled(Builder $query): void
    {
        $query->whereNotNull('rentals.cancelled_at');
    }

    /**
     * Rentals in one of the {@see self::STATUSES}.
     */
    #[Scope]
    protected function inStatus(Builder $query, string $status, ?CarbonInterface $now = null): void
    {
        match ($status) {
            'active' => $query->outNow($now),
            'upcoming' => $query->upcoming($now),
            'completed' => $query->notCancelled()->whereDoesntHave('openItems'),
            'cancelled' => $query->cancelled(),
        };
    }

    /**
     * SQL ranking each rental as Aktif (1), Mendatang (2), Selesai (3) or Dibatalkan (4), aliased status_rank,
     * so lists can sort by status. Takes the current time as its one binding.
     */
    public static function statusRankSql(): string
    {
        $openItems = RentalItem::query()
            ->whereColumn('rental_items.rental_id', 'rentals.id')
            ->whereNull('rental_items.returned_at')
            ->toRawSql();

        return "CASE WHEN rentals.cancelled_at IS NOT NULL THEN 4 WHEN NOT EXISTS ({$openItems}) THEN 3 WHEN rentals.start_date > ? THEN 2 ELSE 1 END as status_rank";
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Only a rental that hasn't started yet can be cancelled; once units are out, they come back through a return.
     */
    public function isCancellable(): bool
    {
        return ! $this->isCancelled() && ! $this->isCompleted() && $this->start_date->isAfter(now()->endOfDay());
    }

    public function isCompleted(): bool
    {
        if ($this->relationLoaded('openItems')) {
            return $this->openItems->isEmpty();
        }

        if ($this->relationLoaded('items')) {
            return $this->items->every(fn (RentalItem $item): bool => $item->isReturned());
        }

        return $this->openItems()->doesntExist();
    }

    public function timing(): RentalTiming
    {
        if ($this->isCancelled()) {
            return RentalTiming::cancelled();
        }

        return $this->isCompleted()
            ? RentalTiming::completed($this->due_date, $this->lastReturnedAt())
            : RentalTiming::for($this->start_date, $this->due_date);
    }

    /**
     * When the last unit came back. Lists should load it with withMax('items', 'returned_at')
     * to avoid one query per row.
     */
    public function lastReturnedAt(): ?CarbonInterface
    {
        if (array_key_exists('items_max_returned_at', $this->attributes)) {
            return $this->items_max_returned_at;
        }

        if ($this->relationLoaded('items')) {
            return $this->items->max('returned_at');
        }

        $lastReturnedAt = $this->items()->max('returned_at');

        return $lastReturnedAt ? Carbon::parse($lastReturnedAt) : null;
    }

    public function bill(): RentalBill
    {
        return RentalBill::for($this);
    }

    /**
     * Recalculate and store total_amount and paid_amount. Call after anything that changes the bill:
     * units, dates, discount, charges, payments or cancellation.
     */
    public function syncTotals(): void
    {
        // Work on a copy so callers aren't left holding freshly loaded relations they didn't ask for.
        $bill = RentalBill::for((clone $this)->unsetRelations()->load(['items.unit.category', 'charges', 'payments']));

        $this->forceFill(['total_amount' => $bill->total, 'paid_amount' => $bill->paid])->saveQuietly();
    }

    public function hasEventDetails(): bool
    {
        return filled($this->event_name) || filled($this->event_address) || filled($this->event_social);
    }
}
