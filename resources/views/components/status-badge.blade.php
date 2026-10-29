@props(['status'])

{{-- $status: an App\Enums\RentalStatus (which picks its own colours), or the unit states "rented" / "available". --}}
<span {{ $attributes->class([
    'badge fs-11',
    $status instanceof \App\Enums\RentalStatus ? $status->badgeClass() : match ($status) {
        'overdue' => 'bg-danger-subtle text-danger',
        'soon' => 'bg-warning-subtle text-warning',
        'available' => 'bg-success-subtle text-success',
        'upcoming' => 'bg-secondary-subtle text-secondary',
        'completed' => 'bg-light text-muted',
        'rented' => 'bg-primary-subtle text-primary',
        default => 'bg-light text-body',
    },
]) }}>{{ $slot }}</span>
