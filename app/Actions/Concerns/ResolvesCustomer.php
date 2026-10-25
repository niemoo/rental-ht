<?php

namespace App\Actions\Concerns;

use App\Enums\DiscountType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Shared by the actions that book or change a rental: pick the customer, tidy optional text, and
 * turn the discount input into rental attributes.
 */
trait ResolvesCustomer
{
    /**
     * The existing customer for customer_id, or a new one from customer_name + customer_phone.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function resolveCustomer(User $user, array $data): Customer
    {
        if (filled($data['customer_id'] ?? null)) {
            return Customer::query()->find($data['customer_id'])
                ?? throw ValidationException::withMessages(['customer_id' => 'Pelanggan tersebut sudah tidak ada.']);
        }

        return Customer::create([
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'],
            'created_by' => $user->id,
        ]);
    }

    /**
     * @param  array{type: string|null, value: int, note: string|null}|null  $discount  A DiscountType value and its amount; no type or a zero value means no discount.
     * @return array{discount_type: DiscountType|null, discount_value: int, discount_note: string|null}
     */
    private static function discountAttributes(?array $discount): array
    {
        $type = DiscountType::tryFrom((string) ($discount['type'] ?? ''));
        $value = max(0, (int) ($discount['value'] ?? 0));

        if ($type === null || $value === 0) {
            return ['discount_type' => null, 'discount_value' => 0, 'discount_note' => null];
        }

        return [
            'discount_type' => $type,
            'discount_value' => $type === DiscountType::Percent ? min($value, 100) : $value,
            'discount_note' => self::nullIfBlank($discount['note'] ?? null),
        ];
    }

    private static function nullIfBlank(?string $value): ?string
    {
        return filled($value) ? trim($value) : null;
    }
}
