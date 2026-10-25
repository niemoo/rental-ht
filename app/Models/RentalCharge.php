<?php

namespace App\Models;

use App\Enums\ChargeType;
use App\Models\Concerns\RecordsAuthor;
use Database\Factories\RentalChargeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An extra charge on a rental on top of the rent (late fee, damage, delivery…), in whole rupiah.
 */
#[Fillable(['rental_id', 'type', 'description', 'amount', 'created_by'])]
class RentalCharge extends Model
{
    /** @use HasFactory<RentalChargeFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ChargeType::class,
            'amount' => 'integer',
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
     * The description, or the type's label when none was given.
     */
    public function label(): string
    {
        return filled($this->description) ? $this->description : $this->type->label();
    }
}
