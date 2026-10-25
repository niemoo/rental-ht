<?php

/*
|--------------------------------------------------------------------------
| Invoice
|--------------------------------------------------------------------------
|
| Business details printed on every rental invoice. Replace the placeholders
| with the real ones (or set them in .env).
|
*/

return [

    'business' => [
        'name' => env('INVOICE_BUSINESS_NAME', 'Kharisma Rental Center'),
        'address' => env('INVOICE_BUSINESS_ADDRESS', 'Jl. Contoh Alamat No. 1, Bandung'),
        'phone' => env('INVOICE_BUSINESS_PHONE', '0812-0000-0000'),
        'email' => env('INVOICE_BUSINESS_EMAIL', 'admin@kharismarental.id'),
    ],

    'bank' => [
        'name' => env('INVOICE_BANK_NAME', 'Bank BCA'),
        'account' => env('INVOICE_BANK_ACCOUNT', '123 456 7890'),
        'holder' => env('INVOICE_BANK_HOLDER', 'Kharisma Rental Center'),
    ],

    'terms' => [
        'Barang dikembalikan paling lambat pada tanggal kembali yang tertera.',
        'Keterlambatan dikenakan biaya sewa harian sesuai tarif.',
        'Kerusakan atau kehilangan barang menjadi tanggung jawab penyewa.',
    ],

];
