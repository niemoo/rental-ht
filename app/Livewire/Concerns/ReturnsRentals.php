<?php

namespace App\Livewire\Concerns;

use App\Actions\ReturnRental;
use App\Enums\Permission;
use App\Models\Rental;
use Illuminate\Support\Facades\Auth;

/**
 * Backs the <x-return-button> confirm dialog.
 */
trait ReturnsRentals
{
    /**
     * @param  array<int|string, string>  $conditions  UnitCondition values keyed by rental item id.
     */
    public function returnRental(int $rentalId, array $conditions = []): void
    {
        $this->authorize(Permission::ReturnRentals);

        app(ReturnRental::class)->handle(Auth::user(), Rental::query()->findOrFail($rentalId), $conditions);
    }
}
