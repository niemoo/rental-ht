@props(['condition'])

{{-- $condition: an App\Enums\UnitCondition. --}}
<span {{ $attributes->class(['badge fs-11', $condition->badgeClass()]) }}>{{ $condition->label() }}</span>
