@props(['rentals'])

@if ($rentals->isNotEmpty())
    <section>
        <h2 class="mb-2.5 px-0.5 text-base font-semibold">
            Mendatang <span class="text-sm font-medium tabular-nums text-muted-foreground">· {{ $rentals->count() }}</span>
        </h2>
        <x-card>
            @php($canViewRentals = auth()->user()->can(\App\Enums\Permission::ViewRentals))
            @foreach ($rentals as $rental)
                <x-link-if
                    :href="route('rentals.show', $rental)"
                    :when="$canViewRentals"
                    as="div"
                    wire:key="upcoming-{{ $rental->id }}"
                    class="flex items-center gap-3 border-b border-border px-3.5 py-3 last:border-b-0"
                    link-class="transition-colors hover:bg-muted/50"
                >
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $rental->customer->name }}</div>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @foreach ($rental->openItems as $item)
                                <x-unit-chip :code="$item->unit->code" />
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-none flex-col items-end gap-1">
                        <x-status-badge status="upcoming">Mendatang</x-status-badge>
                        <span class="text-xs tabular-nums text-muted-foreground">{{ $rental->start_date->translatedFormat('j M') }} · {{ $rental->timing()->label }}</span>
                    </div>
                </x-link-if>
            @endforeach
        </x-card>
    </section>
@endif
