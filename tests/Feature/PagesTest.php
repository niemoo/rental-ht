<?php

use App\Models\Rental;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::query()->where('email', 'staff@gmail.com')->firstOrFail());
});

test('every page renders with seeded data', function (string $route, string $text) {
    $this->get(route($route))->assertOk()->assertSee($text);
})->with([
    'dashboard' => ['dashboard', 'Sedang disewa'],
    'units' => ['units.index', 'A-001'],
    'customers' => ['customers.index', 'Budi Santoso'],
    'history' => ['rentals.index', 'Festival Musik'],
    'new rental' => ['rentals.create', 'Penyewaan baru'],
]);

test('the dashboard shows rentals that are out now and upcoming', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Hendra Gunawan')   // overdue, out now
        ->assertSee('Terlambat 4h')
        ->assertSee('Mendatang');
});

test('the rental detail page shows the booking', function () {
    $rental = Rental::query()->where('event_name', 'Festival Musik')->firstOrFail();

    $this->get(route('rentals.show', $rental))
        ->assertOk()
        ->assertSee('EO Bahagia')
        ->assertSee('Lapangan Kota')
        ->assertSee('Doni (Staff)')
        ->assertSee('Belum kembali');
});

test('a missing rental returns 404', function () {
    $this->get('/rentals/999999')->assertNotFound();
});
