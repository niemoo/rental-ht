<div class="flex flex-col gap-4">
    @php
        $canEdit = auth()->user()->can(\App\Enums\Permission::EditCustomers);
        $canDelete = auth()->user()->can(\App\Enums\Permission::DeleteCustomers);
        $canViewRentals = auth()->user()->can(\App\Enums\Permission::ViewRentals);
    @endphp

    <div>
        <a href="{{ route('customers.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
            <x-icon name="arrow-left" /> Kembali
        </a>
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid size-10 flex-none place-items-center rounded-full bg-accent text-sm font-semibold text-primary">{{ $customer->initials() }}</span>
                <h1 class="truncate text-xl font-semibold tracking-tight lg:text-2xl">{{ $customer->name }}</h1>
            </div>
            <x-customers.row-actions :customer="$customer" :can-edit="$canEdit" :can-delete="$canDelete" delete-action="delete()" />
        </div>
    </div>

    <x-notice :message="$notice" />

    {{-- Details --}}
    <section class="rounded-2xl border border-border bg-card p-4 shadow-xs">
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm lg:grid-cols-4">
            <div><dt class="text-muted-foreground">Nomor HP</dt><dd class="tabular-nums">{{ $customer->phone }}</dd></div>
            <div><dt class="text-muted-foreground">Total penyewaan</dt><dd class="tabular-nums">{{ $customer->rentals_count }}</dd></div>
            <div><dt class="text-muted-foreground">Penyewaan aktif</dt><dd class="tabular-nums">{{ $customer->active_rentals_count }}</dd></div>
            <div><dt class="text-muted-foreground">Unit disewa</dt><dd class="tabular-nums">{{ $customer->units_out_count }}</dd></div>
            <div><dt class="text-muted-foreground">Ditambahkan oleh</dt><dd>{{ $customer->creator?->name ?? '—' }}</dd></div>
            <div><dt class="text-muted-foreground">Ditambahkan pada</dt><dd class="tabular-nums">{{ $customer->created_at->translatedFormat('j M Y') }}</dd></div>
        </dl>
    </section>

    {{-- Rental history --}}
    <div class="flex items-center justify-between gap-2.5">
        <h2 class="text-base font-semibold">
            Riwayat pemesanan <span class="font-medium tabular-nums text-muted-foreground">· {{ $rentals->total() }}</span>
        </h2>
        <x-sort-select
            class="lg:hidden"
            :options="['start' => 'Mulai', 'due' => 'Kembali', 'event' => 'Acara', 'units' => 'Jumlah unit']"
            :sort-by="$sortBy"
            :sort-direction="$sortDirection"
        />
    </div>

    {{-- Desktop table --}}
    <x-card class="hidden lg:block">
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <x-sort-header column="event" :sort-by="$sortBy" :sort-direction="$sortDirection">Acara</x-sort-header>
                    <x-sort-header column="units" :sort-by="$sortBy" :sort-direction="$sortDirection">Unit</x-sort-header>
                    <x-sort-header column="start" :sort-by="$sortBy" :sort-direction="$sortDirection">Mulai</x-sort-header>
                    <x-sort-header column="due" :sort-by="$sortBy" :sort-direction="$sortDirection">Kembali</x-sort-header>
                    <th class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap">Status</th>
                    @if ($canViewRentals)
                        <th class="h-10 px-2 pr-4"><span class="sr-only">Detail</span></th>
                    @endif
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                @forelse ($rentals as $rental)
                    @php($timing = $rental->timing())
                    <tr wire:key="customer-rental-row-{{ $rental->id }}" class="border-b transition-colors hover:bg-muted/50">
                        <td @class(['max-w-72 truncate p-2 pl-4 align-middle', 'text-muted-foreground' => ! $rental->event_name])>
                            <x-link-if :href="route('rentals.show', $rental)" :when="$canViewRentals" link-class="hover:underline">{{ $rental->event_name ?? '—' }}</x-link-if>
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->items_count }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->start_date->translatedFormat('j M Y') }}</td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums">{{ $rental->due_date->translatedFormat('j M Y') }}</td>
                        <td class="p-2 align-middle whitespace-nowrap"><x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge></td>
                        @if ($canViewRentals)
                            <td class="p-2 pr-4 text-right align-middle">
                                <a href="{{ route('rentals.show', $rental) }}" class="inline-flex text-muted-foreground hover:text-foreground" aria-label="Detail penyewaan {{ $rental->start_date->translatedFormat('j M Y') }}">
                                    <x-icon name="chevron-right" />
                                </a>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canViewRentals ? 6 : 5 }}"><x-empty-state>Belum pernah menyewa.</x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    {{-- Mobile list --}}
    <x-card class="lg:hidden">
        @forelse ($rentals as $rental)
            @php($timing = $rental->timing())
            <x-link-if
                :href="route('rentals.show', $rental)"
                :when="$canViewRentals"
                as="div"
                wire:key="customer-rental-item-{{ $rental->id }}"
                class="flex items-center gap-3 border-b border-border px-4 py-3 last:border-b-0"
                link-class="transition-colors hover:bg-muted/50"
            >
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium">{{ $rental->event_name ?? $rental->items_count.' unit' }}</div>
                    <div class="text-xs tabular-nums text-muted-foreground">
                        {{ $rental->start_date->translatedFormat('j M Y') }} → {{ $rental->due_date->translatedFormat('j M Y') }}
                    </div>
                </div>
                <x-status-badge :status="$timing->status">{{ $timing->pill }}</x-status-badge>
                @if ($canViewRentals)
                    <x-icon name="chevron-right" class="text-muted-foreground" />
                @endif
            </x-link-if>
        @empty
            <x-empty-state>Belum pernah menyewa.</x-empty-state>
        @endforelse
    </x-card>

    {{ $rentals->links() }}
</div>
