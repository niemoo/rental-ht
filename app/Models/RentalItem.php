<?php

namespace App\Models;

use App\Enums\UnitCondition;
use App\Models\Concerns\RecordsAuthor;
use Database\Factories\RentalItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['rental_id', 'unit_id', 'daily_rate', 'returned_at', 'return_condition', 'created_by', 'updated_by'])]
class RentalItem extends Model
{
    /** @use HasFactory<RentalItemFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate' => 'integer',
            'returned_at' => 'datetime',
            'return_condition' => UnitCondition::class,
        ];
    }

    /**
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Includes deleted units, so past rentals still show which unit went out.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
