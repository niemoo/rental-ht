@props(['name'])

{{--
    Remix Icon glyphs (https://remixicon.com) from the Velzon template's icon font. The names stay the
    app's own, so views don't change when the icon set does. A Tailwind size-* class still works: it is
    turned into the matching font size, since a font icon is sized by font-size rather than width.
--}}
@php
    $glyphs = [
        'arrow-down' => 'ri-arrow-down-line',
        'arrow-left' => 'ri-arrow-left-line',
        'arrow-up' => 'ri-arrow-up-line',
        'bell' => 'ri-notification-3-line',
        'building' => 'ri-building-line',
        'calendar-days' => 'ri-calendar-event-line',
        'chevron-left' => 'ri-arrow-left-s-line',
        'chevron-right' => 'ri-arrow-right-s-line',
        'filter' => 'ri-filter-3-line',
        'chart-column' => 'ri-bar-chart-box-line',
        'check' => 'ri-check-line',
        'eye' => 'ri-eye-line',
        'download' => 'ri-download-2-line',
        'ellipsis' => 'ri-more-fill',
        'chevrons-up-down' => 'ri-arrow-up-down-line',
        'file-text' => 'ri-file-text-line',
        'history' => 'ri-history-line',
        'layout-grid' => 'ri-dashboard-2-line',
        'list' => 'ri-list-unordered',
        'log-out' => 'ri-logout-box-r-line',
        'mail' => 'ri-mail-line',
        'menu' => 'ri-menu-line',
        'moon' => 'ri-moon-line',
        'package' => 'ri-archive-line',
        'pencil' => 'ri-pencil-line',
        'plus' => 'ri-add-line',
        'printer' => 'ri-printer-line',
        'radio-tower' => 'ri-base-station-line',
        'shield' => 'ri-shield-user-line',
        'search' => 'ri-search-line',
        'sun' => 'ri-sun-line',
        'trash' => 'ri-delete-bin-line',
        'triangle-alert' => 'ri-error-warning-line',
        'user' => 'ri-user-line',
        'user-cog' => 'ri-user-settings-line',
        'users' => 'ri-team-line',
        'wallet' => 'ri-wallet-3-line',
        'x' => 'ri-close-line',
    ];

    $glyph = $glyphs[$name] ?? throw new InvalidArgumentException("Unknown icon [{$name}].");

    $classes = explode(' ', (string) $attributes->get('class', ''));
    $fontSize = null;
    foreach ($classes as $index => $class) {
        if (preg_match('/^size-(\d+(?:\.\d+)?)$/', $class, $match)) {
            $fontSize = ((float) $match[1] * 0.25).'rem';
            unset($classes[$index]);
        } elseif (preg_match('/^size-\[(\d+(?:\.\d+)?px)\]$/', $class, $match)) {
            $fontSize = $match[1];
            unset($classes[$index]);
        }
    }
@endphp

<i
    {{ $attributes->except('class')->merge(['aria-hidden' => 'true']) }}
    @class([$glyph, 'lh-1 flex-shrink-0', ...$classes])
    @if ($fontSize) style="font-size: {{ $fontSize }}" @endif
></i>
