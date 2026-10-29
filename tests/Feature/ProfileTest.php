<?php

use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\User;

test('guests are sent to the login page', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

test('the profile shows who is signed in and their role', function () {
    $user = User::factory()->owner()->create(['name' => 'Rina Kusuma', 'email' => 'rina@example.com']);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Rina Kusuma')
        ->assertSee('rina@example.com')
        ->assertSee('Pemilik');
});

test('staff without user management can open their profile but not edit it', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile'))
        ->assertOk()
        ->assertDontSee('Ubah profil');
});

test('the user menu links to the profile', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('profile'))
        ->assertSee('Profil saya');
});

test('the profile lists the rentals and payments the user recorded, and nobody else\'s', function () {
    $user = User::factory()->owner()->create();
    $other = User::factory()->owner()->create();

    $this->actingAs($other);
    $othersRental = Rental::factory()->create();

    $this->actingAs($user);
    $rental = Rental::factory()->create();
    RentalPayment::factory()->for($rental)->create(['amount' => 250000]);

    $this->get(route('profile'))
        ->assertOk()
        ->assertSee('Membuat penyewaan '.$rental->invoice_number)
        ->assertSee('Mencatat pembayaran Rp250.000')
        ->assertDontSee($othersRental->invoice_number);
});
