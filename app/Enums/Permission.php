<?php

namespace App\Enums;

/**
 * Every permission the app checks. Roles get them via the Roles screen; defaults for the
 * built-in roles live in {@see Role::defaultPermissions()}.
 *
 * After adding or removing a case, sync the database with:
 * php artisan db:seed --class=RolesAndPermissionsSeeder
 */
enum Permission: string
{
    case ViewUnits = 'units.view';
    case CreateUnits = 'units.create';
    case EditUnits = 'units.edit';
    case DeleteUnits = 'units.delete';

    case ViewCategories = 'categories.view';
    case CreateCategories = 'categories.create';
    case EditCategories = 'categories.edit';
    case DeleteCategories = 'categories.delete';

    case ViewCustomers = 'customers.view';
    case CreateCustomers = 'customers.create';
    case EditCustomers = 'customers.edit';
    case DeleteCustomers = 'customers.delete';

    case ViewRentals = 'rentals.view';
    case CreateRentals = 'rentals.create';
    case EditRentals = 'rentals.edit';
    case ReturnRentals = 'rentals.return';
    case CancelRentals = 'rentals.cancel';
    case GiveDiscounts = 'rentals.discount';
    case ManageCharges = 'rentals.charges';

    case RecordPayments = 'payments.record';
    case DeletePayments = 'payments.delete';

    case ViewReports = 'reports.view';

    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';

    public function label(): string
    {
        return match ($this) {
            self::ViewUnits => 'Lihat unit',
            self::CreateUnits => 'Tambah unit',
            self::EditUnits => 'Ubah unit',
            self::DeleteUnits => 'Hapus unit',
            self::ViewCategories => 'Lihat kategori',
            self::CreateCategories => 'Tambah kategori',
            self::EditCategories => 'Ubah kategori',
            self::DeleteCategories => 'Hapus kategori',
            self::ViewCustomers => 'Lihat pelanggan',
            self::CreateCustomers => 'Tambah pelanggan',
            self::EditCustomers => 'Ubah pelanggan',
            self::DeleteCustomers => 'Hapus pelanggan',
            self::ViewRentals => 'Lihat riwayat & detail penyewaan',
            self::CreateRentals => 'Buat penyewaan',
            self::EditRentals => 'Ubah penyewaan',
            self::ReturnRentals => 'Proses pengembalian',
            self::CancelRentals => 'Batalkan penyewaan',
            self::GiveDiscounts => 'Beri diskon',
            self::ManageCharges => 'Kelola biaya tambahan',
            self::RecordPayments => 'Catat pembayaran',
            self::DeletePayments => 'Hapus pembayaran',
            self::ViewReports => 'Lihat laporan',
            self::ManageUsers => 'Kelola pengguna',
            self::ManageRoles => 'Kelola role & izin',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ViewUnits, self::CreateUnits, self::EditUnits, self::DeleteUnits => 'Unit',
            self::ViewCategories, self::CreateCategories, self::EditCategories, self::DeleteCategories => 'Kategori',
            self::ViewCustomers, self::CreateCustomers, self::EditCustomers, self::DeleteCustomers => 'Pelanggan',
            self::ViewRentals, self::CreateRentals, self::EditRentals, self::ReturnRentals,
            self::CancelRentals, self::GiveDiscounts, self::ManageCharges => 'Penyewaan',
            self::RecordPayments, self::DeletePayments => 'Pembayaran',
            self::ViewReports => 'Laporan',
            self::ManageUsers, self::ManageRoles => 'Pengaturan',
        };
    }

    /**
     * All cases keyed by group label, in declaration order.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->group()][] = $permission;
        }

        return $groups;
    }
}
