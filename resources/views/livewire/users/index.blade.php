@php
    /** Velzon badge colours for a user's role: Superadmin, Owner, any other role, or none. */
    $roleBadge = fn (\App\Models\User $user): string => match (true) {
        $user->isSuperadmin() => 'bg-primary-subtle text-primary',
        $user->isOwner() => 'bg-success-subtle text-success',
        $user->roleName() !== null => 'bg-info-subtle text-info',
        default => 'bg-light text-muted',
    };
@endphp
<div class="vstack gap-4">
    <x-page-header title="Pengguna">
        <x-slot:subtitle>
            <span class="tabular-nums">{{ Number::format($totalCount) }}</span> pengguna dapat masuk ke aplikasi
        </x-slot:subtitle>
        <x-slot:action>
            <x-button variant="success" :href="route('users.create')"><x-icon name="plus" />Tambah pengguna</x-button>
        </x-slot:action>
    </x-page-header>

    <x-notice :message="$notice" />

    <div class="card mb-0">
        <div class="card-body border-bottom border-bottom-dashed">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="max-width: 28rem">
                    <x-search-input wire:model.live.debounce.250ms="search" placeholder="Cari nama atau email…" />
                </div>
                <x-sort-select
                    class="d-lg-none"
                    :options="['name' => 'Nama', 'email' => 'Email', 'joined' => 'Bergabung']"
                    :sort-by="$sortBy"
                    :sort-direction="$sortDirection"
                />
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="card-body d-none d-lg-block">
            <div class="table-responsive table-card">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <x-sort-header column="name" :sort-by="$sortBy" :sort-direction="$sortDirection">Nama</x-sort-header>
                            <x-sort-header column="email" :sort-by="$sortBy" :sort-direction="$sortDirection">Email</x-sort-header>
                            <th>Role</th>
                            <x-sort-header column="joined" :sort-by="$sortBy" :sort-direction="$sortDirection">Bergabung</x-sort-header>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr wire:key="user-row-{{ $user->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs flex-shrink-0">
                                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-semibold fs-11">{{ $user->initials() }}</span>
                                        </div>
                                        <span class="fw-medium">{{ $user->name }}</span>
                                        @if ($user->is(auth()->user()))
                                            <span class="badge bg-light text-muted">Anda</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-muted">{{ $user->email }}</td>
                                <td><span class="badge {{ $roleBadge($user) }}">{{ $user->roleLabel() }}</span></td>
                                <td class="text-muted tabular-nums">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                                <td class="text-end"><x-users.row-actions :user="$user" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state>Tidak ada pengguna yang cocok.</x-empty-state></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile list --}}
        <ul class="list-group list-group-flush d-lg-none">
            @forelse ($users as $user)
                <li wire:key="user-item-{{ $user->id }}" class="list-group-item d-flex align-items-center gap-3 py-3">
                    <div class="avatar-xs flex-shrink-0">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-semibold fs-11">{{ $user->initials() }}</span>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-medium text-truncate">{{ $user->name }}</span>
                            @if ($user->is(auth()->user()))
                                <span class="badge bg-light text-muted">Anda</span>
                            @endif
                        </div>
                        <div class="text-muted fs-12 text-truncate">{{ $user->email }}</div>
                        <span class="badge {{ $roleBadge($user) }} mt-1">{{ $user->roleLabel() }}</span>
                    </div>
                    <x-users.row-actions :user="$user" />
                </li>
            @empty
                <li class="list-group-item"><x-empty-state>Tidak ada pengguna yang cocok.</x-empty-state></li>
            @endforelse
        </ul>

        @if ($users->total() > 0)
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>
</div>
