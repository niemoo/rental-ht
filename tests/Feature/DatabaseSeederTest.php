<?php

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.superadmin' => ['name' => 'Pak Admin', 'email' => 'admin@rental.test', 'password' => 'rahasia-prod']]);
});

test('production gets only the built-in roles and the superadmin from the env', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $superadmin = User::query()->sole();

    expect(RoleModel::query()->pluck('name')->sort()->values()->all())->toBe(['owner', 'staff', 'superadmin'])
        ->and($superadmin->email)->toBe('admin@rental.test')
        ->and($superadmin->name)->toBe('Pak Admin')
        ->and($superadmin->hasRole(Role::Superadmin))->toBeTrue()
        ->and(Hash::check('rahasia-prod', $superadmin->password))->toBeTrue()
        ->and(Customer::query()->count() + Unit::query()->count() + Rental::query()->count())->toBe(0);
});

test('production refuses to create the superadmin without a password', function () {
    config(['app.superadmin.password' => null]);

    $this->artisan('db:seed', ['--force' => true]);
})->throws(RuntimeException::class, 'SUPERADMIN_PASSWORD');

test('the demo seeder never wipes production data', function () {
    $customer = Customer::factory()->create();

    $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

    expect(Customer::query()->sole()->is($customer))->toBeTrue();
});
