@props(['rentals', 'sortBy', 'sortDirection'])

{{--
    Rentals currently out: a table on desktop, cards on mobile. $rentals is a paginator with
    customer + openItems.unit loaded; the host component uses the SortsTable concern.
--}}
@php
    $rows = $rentals->map(fn ($rental) => ['rental' => $rental, 'timing' => $rental->timing()]);
    $canViewRentals = auth()->user()->can(\App\Enums\Permission::ViewRentals);
@endphp

<x-sort-select
    class="mb-3 lg:hidden"
    :options="['due' => 'Jatuh tempo', 'start' => 'Mulai', 'customer' => 'Pelanggan', 'units' => 'Jumlah unit']"
    :sort-by="$sortBy"
    :sort-direction="$sortDirection"
/>

{{-- Desktop table --}}
<x-card class="hidden lg:block">
    <div class="overflow-x-auto"><table class="w-full caption-bottom text-sm">
        <thead class="[&_tr]:border-b">
            <tr class="border-b">
                <x-sort-header column="customer" :sort-by="$sortBy" :sort-direction="$sortDirection">Pelanggan</x-sort-header>
                <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Mulai</x-sort-header>
                <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Jatuh tempo</x-sort-header>
                <th class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap">Status</th>
                <th class="h-10 px-2 pr-4"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody class="[&_tr:last-child]:border-0">
            @forelse ($rows as ['rental' => $rental, 'timing' => $timing])
                <tr wire:key="out-row-{{ $rental->id }}" @class([
                    'border-b transition-colors hover:bg-muted/50',
                    '[&>td:first-child]:shadow-[inset_3px_0_0_var(--color-red-600)]' => $timing->status === \App\Enums\RentalStatus::Overdue,
                ])>
                    <td class="p-2 pl-4 align-middle whitespace-nowrap">
                        <x-link-if :href="route('rentals.show', $rental)" :when="$canViewRentals" class="font-medium" link-class="hover:underline">{{ $rental->customer->name }}</x-link-if>
                        <div class="text-xs tabular-nums text-muted-foreground">{{ $rental->customer->phone }}</div>
                    </td>
                    <td class="p-2 align-middle whitespace-nowrap">
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($rental->openItems as $item)
                                <x-unit-chip :code="$item->unit->code" />
                            @endforeach
                        </div>
                    </td>
                    <td class="p-2 align-middle whitespace-nowrap tabular-nums text-muted-foreground">{{ $rental->start_date->translatedFormat('j M') }}</td>
                    <td class="p-2 align-middle whitespace-nowrap">
                        <div class="font-medium tabular-nums">{{ $rental->due_date->translatedFormat('j M') }}</div>
                        <div class="text-xs tabular-nums {{ $timing->status->textClass() }}">{{ $timing->label }}</div>
                    </td>
                    <td class="p-2 align-middle whitespace-nowrap"><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                    <td class="p-2 pr-4 text-right align-middle"><x-return-button :rental="$rental" /></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state>Tidak ada yang sedang disewa.</x-empty-state></td></tr>
            @endforelse
        </tbody>
    </table></div>
</x-card>

{{-- Mobile cards --}}
<div class="flex flex-col gap-2.5 lg:hidden">
    @forelse ($rows as ['rental' => $rental, 'timing' => $timing])
        @php($overdue = $timing->status === \App\Enums\RentalStatus::Overdue)
        <div wire:key="out-card-{{ $rental->id }}" @class(['relative rounded-2xl border bg-card p-3.5 shadow-xs', 'border-red-200 dark:border-red-500/30' => $overdue, 'border-border' => ! $overdue])>
            @if ($overdue)
                <span class="absolute inset-y-3 left-0 w-[3px] rounded-r bg-red-600"></span>
            @endif
            <div class="flex items-start justify-between gap-2.5">
                <x-link-if :href="route('rentals.show', $rental)" :when="$canViewRentals" as="div">
                    <div class="text-[15px] font-medium">{{ $rental->customer->name }}</div>
                    <div class="text-xs tabular-nums text-muted-foreground">{{ $rental->customer->phone }}</div>
                </x-link-if>
                <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
            </div>
            <div class="my-2.5 flex flex-wrap gap-1.5">
                @foreach ($rental->openItems as $item)
                    <x-unit-chip :code="$item->unit->code" />
                @endforeach
            </div>
            <div class="flex items-center justify-between gap-2.5 border-t border-border pt-2.5">
                <div class="flex items-center gap-1.5 text-[13px] text-muted-foreground">
                    <x-icon name="calendar-days" class="size-[15px]" />
                    <span @class(['font-medium '.$timing->status->textClass() => in_array($timing->status, [\App\Enums\RentalStatus::Overdue, \App\Enums\RentalStatus::Soon], true)])>{{ $timing->label }}</span>
                </div>
                <x-return-button :rental="$rental" />
            </div>
        </div>
    @empty
        <x-card><x-empty-state>Tidak ada yang sedang disewa.</x-empty-state></x-card>
    @endforelse
</div>

<div class="mt-3">{{ $rentals->links() }}</div>
