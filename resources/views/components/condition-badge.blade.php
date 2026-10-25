@props(['condition'])

{{-- $condition: an App\Enums\UnitCondition. --}}
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap', $condition->badgeClass()]) }}>
    <span class="size-1.5 rounded-full bg-current opacity-90"></span>
    {{ $condition->label() }}
</span>
