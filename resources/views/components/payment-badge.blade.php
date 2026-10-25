@props(['status'])

{{-- Pill for an App\Enums\PaymentStatus. --}}
<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
    $status->badgeClass(),
]) }}>
    <span class="size-1.5 rounded-full bg-current opacity-90"></span>
    {{ $slot->isEmpty() ? $status->label() : $slot }}
</span>
