@props(['href', 'when', 'as' => 'span', 'linkClass' => ''])

{{--
    A link only when $when is true (e.g. the user can open the target page); otherwise the same
    content as a plain <span>/<div>, so users never see links that lead to a 403.
    $linkClass holds hover/cursor styles that only make sense on a real link.
--}}
@if ($when)
    <a href="{{ $href }}" {{ $attributes->class($linkClass) }}>{{ $slot }}</a>
@else
    <{{ $as }} {{ $attributes }}>{{ $slot }}</{{ $as }}>
@endif
