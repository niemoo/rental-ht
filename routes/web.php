<?php

use App\Enums\Permission;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\RentalInvoiceController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Auth\Login;
use App\Livewire\Categories;
use App\Livewire\Charges;
use App\Livewire\Customers;
use App\Livewire\Dashboard;
use App\Livewire\Payments;
use App\Livewire\Profile;
use App\Livewire\Rentals;
use App\Livewire\Reports;
use App\Livewire\Roles;
use App\Livewire\Units;
use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('/profile', Profile::class)->name('profile');
    Route::livewire('/units', Units\Index::class)->name('units.index')->can(Permission::ViewUnits);
    Route::livewire('/units/new', Units\Form::class)->name('units.create')->can(Permission::CreateUnits);
    Route::livewire('/units/{unit}/edit', Units\Form::class)->name('units.edit')->can(Permission::EditUnits);
    Route::livewire('/categories', Categories\Index::class)->name('categories.index')->can(Permission::ViewCategories);
    Route::livewire('/categories/new', Categories\Form::class)->name('categories.create')->can(Permission::CreateCategories);
    Route::livewire('/categories/{category}/edit', Categories\Form::class)->name('categories.edit')->can(Permission::EditCategories);
    Route::livewire('/customers', Customers\Index::class)->name('customers.index')->can(Permission::ViewCustomers);
    Route::livewire('/customers/new', Customers\Form::class)->name('customers.create')->can(Permission::CreateCustomers);
    Route::livewire('/customers/{customer}', Customers\Show::class)->name('customers.show')->can(Permission::ViewCustomers);
    Route::livewire('/customers/{customer}/edit', Customers\Form::class)->name('customers.edit')->can(Permission::EditCustomers);
    Route::livewire('/history', Rentals\Index::class)->name('rentals.index')->can(Permission::ViewRentals);
    Route::livewire('/rentals/new', Rentals\Form::class)->name('rentals.create')->can(Permission::CreateRentals);
    Route::livewire('/rentals/{rental}', Rentals\Show::class)->name('rentals.show')->can(Permission::ViewRentals);
    Route::livewire('/rentals/{rental}/edit', Rentals\Form::class)->name('rentals.edit')->can(Permission::EditRentals);
    Route::livewire('/rentals/{rental}/cancel', Rentals\Cancel::class)->name('rentals.cancel')->can(Permission::CancelRentals);
    Route::livewire('/rentals/{rental}/return', Rentals\ReturnUnits::class)->name('rentals.return')->can(Permission::ReturnRentals);
    Route::livewire('/rentals/{rental}/payments/new', Payments\Form::class)->name('rentals.payments.create')->can(Permission::RecordPayments);
    Route::livewire('/rentals/{rental}/charges/new', Charges\Form::class)->name('rentals.charges.create')->can(Permission::ManageCharges);
    Route::get('/rentals/{rental}/invoice', RentalInvoiceController::class)->name('rentals.invoice')->can(Permission::ViewRentals);

    Route::redirect('/reports', '/reports/revenue')->name('reports.index');
    Route::livewire('/reports/revenue', Reports\Revenue::class)->name('reports.revenue')->can(Permission::ViewReports);
    Route::livewire('/reports/receivables', Reports\Receivables::class)->name('reports.receivables')->can(Permission::ViewReports);
    Route::livewire('/reports/rentals', Reports\Rentals::class)->name('reports.rentals')->can(Permission::ViewReports);
    Route::get('/reports/{report}/print', [ReportExportController::class, 'print'])
        ->whereIn('report', ['revenue', 'receivables', 'rentals'])->name('reports.print')->can(Permission::ViewReports);
    Route::get('/reports/{report}/download/{format}', [ReportExportController::class, 'download'])
        ->whereIn('report', ['revenue', 'receivables', 'rentals'])->whereIn('format', ['csv', 'xlsx'])->name('reports.download')->can(Permission::ViewReports);

    Route::livewire('/settings/users', Users\Index::class)->name('users.index')->can(Permission::ManageUsers);
    Route::livewire('/settings/users/new', Users\Form::class)->name('users.create')->can(Permission::ManageUsers);
    Route::livewire('/settings/users/{user}/edit', Users\Form::class)->name('users.edit')->can(Permission::ManageUsers);
    Route::livewire('/settings/roles', Roles\Index::class)->name('roles.index')->can(Permission::ManageRoles);
});
