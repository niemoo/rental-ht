@props(['status'])

{{-- Pill for an App\Enums\PaymentStatus. --}}
<span {{ $attributes->class(['badge fs-11', $status->badgeClass()]) }}>{{ $slot->isEmpty() ? $status->label() : $slot }}</span>
