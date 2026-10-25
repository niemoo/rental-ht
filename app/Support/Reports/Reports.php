<?php

namespace App\Support\Reports;

use App\Enums\PaymentMethod;
use App\Models\Rental;
use App\Support\ReportPeriod;

/**
 * Builds a report from URL query values (period, from, to, method[], aging[], status[]) — the same names
 * the report pages keep in their URL — and cleans the filters, dropping anything unknown.
 */
final class Reports
{
    /** Report slugs as used in URLs, with their tab labels. */
    public const NAMES = ['revenue' => 'Pendapatan', 'receivables' => 'Piutang', 'rentals' => 'Penyewaan'];

    /**
     * @param  array<string, mixed>  $input
     */
    public static function make(string $name, array $input): Report
    {
        $period = ReportPeriod::fromInput(self::string($input['period'] ?? null), self::string($input['from'] ?? null), self::string($input['to'] ?? null));

        return match ($name) {
            'revenue' => new RevenueReport($period, self::methods($input['method'] ?? [])),
            'receivables' => new ReceivablesReport(self::agings($input['aging'] ?? [])),
            'rentals' => new RentalsReport($period, self::statuses($input['status'] ?? [])),
        };
    }

    /**
     * @return list<PaymentMethod>
     */
    public static function methods(mixed $values): array
    {
        return collect((array) $values)
            ->filter(fn (mixed $value): bool => is_string($value))
            ->map(fn (string $value): ?PaymentMethod => PaymentMethod::tryFrom($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function agings(mixed $values): array
    {
        return self::keysOf($values, ReceivablesReport::AGINGS);
    }

    /**
     * @return list<string>
     */
    public static function statuses(mixed $values): array
    {
        return self::keysOf($values, Rental::STATUSES);
    }

    /**
     * @param  array<string, string>  $options
     * @return list<string>
     */
    private static function keysOf(mixed $values, array $options): array
    {
        return collect((array) $values)
            ->filter(fn (mixed $value): bool => is_string($value) && array_key_exists($value, $options))
            ->unique()
            ->values()
            ->all();
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
