<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\RecordsAuthor;
use Database\Factories\RentalPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Money received for a rental, in whole rupiah. A negative amount is a refund to the customer.
 */
#[Fillable(['rental_id', 'amount', 'paid_at', 'method', 'note', 'created_by'])]
class RentalPayment extends Model
{
    /** @use HasFactory<RentalPaymentFactory> */
    use HasFactory, RecordsAuthor, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
            'method' => PaymentMethod::class,
        ];
    }

    /**
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function isRefund(): bool
    {
        return $this->amount < 0;
    }
}
