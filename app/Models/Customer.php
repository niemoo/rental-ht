<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthor;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'phone', 'created_by'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /**
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Units this customer currently has out (unreturned items across all live rentals).
     *
     * @return HasManyThrough<RentalItem, Rental, $this>
     */
    public function openRentalItems(): HasManyThrough
    {
        return $this->hasManyThrough(RentalItem::class, Rental::class)->whereNull('rental_items.returned_at');
    }

    /**
     * Rentals that still have units out or booked must be finished first.
     */
    public function isDeletable(): bool
    {
        return $this->rentals()->open()->doesntExist();
    }

    /**
     * Remove the customer. One with rental history is soft-deleted so past rentals still show who
     * rented; one who never rented is deleted for good.
     */
    public function remove(): void
    {
        if ($this->rentals()->withTrashed()->exists()) {
            $this->delete();
        } else {
            $this->forceDelete();
        }
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->replaceMatches('/[^A-Za-z ]/', '')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
