<?php

use App\Enums\RentalStatus;
use App\Support\RentalTiming;
use Carbon\CarbonImmutable;

$now = CarbonImmutable::parse('2026-10-14 15:00');

test('classifies a rental relative to today', function (string $start, string $due, RentalStatus $status, string $label, string $pill) use ($now) {
    $timing = RentalTiming::for(CarbonImmutable::parse($start), CarbonImmutable::parse($due), $now);

    expect($timing->status)->toBe($status)
        ->and($timing->label)->toBe($label)
        ->and($timing->pill)->toBe($pill);
})->with([
    'starts tomorrow' => ['2026-10-15 00:00', '2026-10-18 23:59', RentalStatus::Upcoming, 'mulai besok', 'Mendatang'],
    'starts in 3 days' => ['2026-10-17 00:00', '2026-10-18 23:59', RentalStatus::Upcoming, 'mulai 3 hari lagi', 'Mendatang'],
    'overdue by 1 day' => ['2026-10-10 00:00', '2026-10-13 23:59', RentalStatus::Overdue, '1 hari lalu', 'Terlambat 1h'],
    'overdue by 4 days' => ['2026-10-01 00:00', '2026-10-10 23:59', RentalStatus::Overdue, '4 hari lalu', 'Terlambat 4h'],
    'due today' => ['2026-10-12 00:00', '2026-10-14 23:59', RentalStatus::Soon, 'selesai hari ini', 'Segera'],
    'due tomorrow' => ['2026-10-14 00:00', '2026-10-15 23:59', RentalStatus::Soon, 'selesai besok', 'Segera'],
    'due in 5 days' => ['2026-10-13 00:00', '2026-10-19 23:59', RentalStatus::OnTime, 'selesai 5 hari lagi', 'Berjalan'],
]);

test('a completed rental is late only when its last unit came back after the due day', function (?string $lastReturnedAt, RentalStatus $status, string $pill) {
    $timing = RentalTiming::completed(CarbonImmutable::parse('2026-10-14 23:59'), $lastReturnedAt ? CarbonImmutable::parse($lastReturnedAt) : null);

    expect($timing->status)->toBe($status)
        ->and($timing->pill)->toBe($pill);
})->with([
    'returned early' => ['2026-10-12 10:00', RentalStatus::Completed, 'Selesai'],
    'returned on the due day' => ['2026-10-14 23:30', RentalStatus::Completed, 'Selesai'],
    'returned a day late' => ['2026-10-15 08:00', RentalStatus::CompletedLate, 'Selesai, telat 1h'],
    'returned 3 days late' => ['2026-10-17 12:00', RentalStatus::CompletedLate, 'Selesai, telat 3h'],
    'no return recorded' => [null, RentalStatus::Completed, 'Selesai'],
]);
