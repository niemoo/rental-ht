<div class="flex flex-col gap-4">
    <x-page-header title="Pengguna">
        <x-slot:subtitle>
            <span class="tabular-nums">{{ $totalCount }}</span> pengguna dapat masuk ke aplikasi
        </x-slot:subtitle>
        <x-slot:action>
            <x-button :href="route('users.create')"><x-icon name="plus" />Tambah pengguna</x-button>
        </x-slot:action>
    </x-page-header>

    @if ($notice)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-2.5 text-sm shadow-xs">
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" class="text-muted-foreground hover:text-foreground" aria-label="Tutup">
                <x-icon name="x" />
            </button>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2.5">
        <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari nama atau email…" />
        <x-sort-select
            class="lg:hidden"
            :options="['name' => 'Nama', 'email' => 'Email', 'joined' => 'Bergabung']"
            :sort-by="$sortBy"
            :sort-direction="$sortDirection"
        />
    </div>

    {{-- Desktop table --}}
    <x-card class="hidden lg:block">
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <x-sort-header column="name" :sort-by="$sortBy" :sort-direction="$sortDirection">Nama</x-sort-header>
                    <x-sort-header column="email" :sort-by="$sortBy" :sort-direction="$sortDirection">Email</x-sort-header>
                    <th class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap">Role</th>
                    <x-sort-header column="joined" :sort-by="$sortBy" :sort-direction="$sortDirection">Bergabung</x-sort-header>
                    <th class="h-10 px-2 pr-4"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                @forelse ($users as $user)
                    <tr wire:key="user-row-{{ $user->id }}" class="border-b transition-colors hover:bg-muted/50">
                        <td class="p-2 pl-4 align-middle whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <span class="grid size-8 flex-none place-items-center rounded-full bg-accent text-xs font-semibold text-primary">{{ $user->initials() }}</span>
                                <span class="font-medium">{{ $user->name }}</span>
                                @if ($user->is(auth()->user()))
                                    <span class="rounded-full bg-muted px-1.5 py-0.5 text-[11px] font-medium text-muted-foreground">Anda</span>
                                @endif
                            </div>
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-muted-foreground">{{ $user->email }}</td>
                        <td class="p-2 align-middle whitespace-nowrap">
                            <x-status-badge :status="match (true) { $user->isSuperadmin() => 'upcoming', $user->isOwner() => 'available', $user->roleName() !== null => 'rented', default => 'soon' }">
                                {{ $user->roleLabel() }}
                            </x-status-badge>
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap tabular-nums text-muted-foreground">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                        <td class="p-2 pr-4 align-middle"><x-users.row-actions :user="$user" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state>Tidak ada pengguna yang cocok.</x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    {{-- Mobile list --}}
    <x-card class="lg:hidden">
        @forelse ($users as $user)
            <div wire:key="user-item-{{ $user->id }}" class="flex items-center gap-3 border-b border-border px-4 py-3 last:border-b-0">
                <span class="grid size-9 flex-none place-items-center rounded-full bg-accent text-xs font-semibold text-primary">{{ $user->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="truncate text-sm font-medium">{{ $user->name }}</span>
                        @if ($user->is(auth()->user()))
                            <span class="rounded-full bg-muted px-1.5 py-0.5 text-[11px] font-medium text-muted-foreground">Anda</span>
                        @endif
                    </div>
                    <div class="truncate text-xs text-muted-foreground">{{ $user->email }} · {{ $user->roleLabel() }}</div>
                </div>
                <x-users.row-actions :user="$user" />
            </div>
        @empty
            <x-empty-state>Tidak ada pengguna yang cocok.</x-empty-state>
        @endforelse
    </x-card>

    {{ $users->links() }}
</div>
