<?php

namespace App\Enums;

/**
 * The built-in roles. They cannot be renamed or deleted from the Roles screen;
 * any other role is created there.
 */
enum Role: string
{
    case Superadmin = 'superadmin';
    case Owner = 'owner';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Owner => 'Pemilik',
            self::Staff => 'Staf',
        };
    }

    /**
     * Display name for any role name, built-in or custom.
     */
    public static function labelFor(string $name): string
    {
        return self::tryFrom($name)?->label() ?? $name;
    }

    /**
     * Permissions given when the role is first created. Owner and staff can be changed later on
     * the Roles screen; the superadmin always keeps every permission.
     *
     * @return list<Permission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Superadmin, self::Owner => Permission::cases(),
            self::Staff => [
                Permission::ViewUnits,
                Permission::ViewCategories,
                Permission::ViewCustomers,
                Permission::CreateCustomers,
                Permission::EditCustomers,
                Permission::ViewRentals,
                Permission::CreateRentals,
                Permission::EditRentals,
                Permission::ReturnRentals,
                Permission::GiveDiscounts,
                Permission::ManageCharges,
                Permission::RecordPayments,
            ],
        };
    }

    /**
     * Whether the role's permissions are fixed to "everything" and can't be edited.
     */
    public function hasLockedPermissions(): bool
    {
        return $this === self::Superadmin;
    }
}
