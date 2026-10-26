<?php

namespace App\Support;

/**
 * How many rows a paginated table shows per page: one of a few fixed choices the user picks
 * under the table (kept in the URL as per_page), so a hand-edited URL can't ask for any other size.
 */
final class PageSize
{
    /** @var list<int> */
    public const OPTIONS = [10, 20, 50, 100];

    public const DEFAULT = 10;

    /**
     * The chosen size, or the default when the value isn't one of the choices.
     */
    public static function from(mixed $value): int
    {
        $size = filter_var($value, FILTER_VALIDATE_INT);

        return in_array($size, self::OPTIONS, true) ? $size : self::DEFAULT;
    }
}
