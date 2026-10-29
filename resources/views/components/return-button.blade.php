@props(['rental'])

{{-- Opens the return page (rentals.return), where staff pick the condition each unit came back in. Hidden without the permission. --}}
@can(\App\Enums\Permission::ReturnRentals)
    <a href="{{ route('rentals.return', $rental) }}" class="btn btn-soft-success btn-sm">Kembalikan</a>
@endcan
