@php
    $canViewRentals = $user->can(\App\Enums\Permission::ViewRentals);
    $canEditProfile = $user->can(\App\Enums\Permission::ManageUsers) && $user->can('update', $user);
    $granted = $permissions->flatten(1)->where('granted', true)->count();
    $permissionTotal = $permissions->flatten(1)->count();
    $permissionShare = $permissionTotal > 0 ? (int) round($granted / $permissionTotal * 100) : 0;
    $tabs = ['overview' => ['Ringkasan', 'layout-grid'], 'activities' => ['Aktivitas', 'list'], 'permissions' => ['Izin', 'shield']];
@endphp

<div x-data="{ tab: 'overview' }">
    {{-- Cover --}}
    <div class="profile-foreground position-relative mx-n4 mt-n4">
        <div class="profile-wid-bg">
            <img src="{{ asset('velzon/images/profile-bg.jpg') }}" alt="" class="profile-wid-img">
        </div>
    </div>

    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
        <div class="row g-4">
            <div class="col-auto">
                <div class="avatar-lg">
                    <span class="avatar-title img-thumbnail rounded-circle bg-primary text-white fs-24 fw-semibold">{{ $user->initials() }}</span>
                </div>
            </div>
            <div class="col">
                <div class="p-2">
                    <h3 class="text-white mb-1">{{ $user->name }}</h3>
                    <p class="text-white text-opacity-75">{{ $user->roleLabel() }}</p>
                    <div class="hstack flex-wrap text-white-50 gap-1">
                        <div class="me-2"><x-icon name="mail" class="me-1 text-white text-opacity-75 fs-16 align-middle" />{{ $user->email }}</div>
                        <div><x-icon name="building" class="me-1 text-white text-opacity-75 fs-16 align-middle" />{{ config('invoice.business.name') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="d-flex flex-wrap gap-2 profile-wrapper">
        <ul class="nav nav-pills animation-nav profile-nav gap-2 gap-lg-3 flex-grow-1" role="tablist">
            @foreach ($tabs as $key => [$label, $icon])
                <li class="nav-item" role="presentation">
                    <button
                        type="button"
                        role="tab"
                        @class(['nav-link fs-14', 'active' => $key === 'overview'])
                        x-bind:class="{ active: tab === '{{ $key }}' }"
                        x-bind:aria-selected="tab === '{{ $key }}'"
                        x-on:click="tab = '{{ $key }}'"
                    >
                        <x-icon :name="$icon" class="d-inline-block d-md-none" />
                        <span class="d-none d-md-inline-block">{{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        @if ($canEditProfile)
            <div class="flex-shrink-0">
                <a href="{{ route('users.edit', $user) }}" class="btn btn-success"><x-icon name="pencil" class="align-bottom" /> Ubah profil</a>
            </div>
        @endif
    </div>

    <div class="tab-content pt-4 text-muted">
        {{-- Ringkasan --}}
        <div class="tab-pane active" x-bind:class="{ active: tab === 'overview' }" role="tabpanel">
            <div class="row">
                <div class="col-xxl-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Info</h5>
                            <div class="table-responsive">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr><th class="ps-0 text-nowrap" scope="row">Nama :</th><td class="text-muted">{{ $user->name }}</td></tr>
                                        <tr><th class="ps-0 text-nowrap" scope="row">Email :</th><td class="text-muted text-break">{{ $user->email }}</td></tr>
                                        <tr><th class="ps-0 text-nowrap" scope="row">Role :</th><td class="text-muted">{{ $user->roleLabel() }}</td></tr>
                                        <tr><th class="ps-0 text-nowrap" scope="row">Usaha :</th><td class="text-muted">{{ config('invoice.business.name') }}</td></tr>
                                        <tr><th class="ps-0 text-nowrap" scope="row">Bergabung :</th><td class="text-muted tabular-nums">{{ $user->created_at->translatedFormat('j F Y') }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Cakupan izin</h5>
                            <p class="text-muted fs-13 mb-4 tabular-nums">{{ $granted }} dari {{ $permissionTotal }} izin dari role {{ $user->roleLabel() }}</p>
                            <div class="progress animated-progress custom-progress progress-label">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $permissionShare }}%" aria-valuenow="{{ $permissionShare }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="label">{{ $permissionShare }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-9">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 me-2 flex-grow-1">Aktivitas terbaru</h4>
                            @if ($activities->count() > 4)
                                <button type="button" class="btn btn-soft-primary btn-sm" x-on:click="tab = 'activities'">Lihat semua</button>
                            @endif
                        </div>
                        <div class="card-body">
                            <x-profile.activity-timeline :items="$activities->take(4)" :can-view-rentals="$canViewRentals" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Aktivitas --}}
        <div class="tab-pane" x-bind:class="{ active: tab === 'activities' }" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aktivitas</h5>
                    <p class="text-muted fs-13 mb-0 mt-1">Penyewaan dan pembayaran terakhir yang dicatat akun ini</p>
                </div>
                <div class="card-body">
                    <x-profile.activity-timeline :items="$activities" :can-view-rentals="$canViewRentals" />
                </div>
            </div>
        </div>

        {{-- Izin --}}
        <div class="tab-pane" x-bind:class="{ active: tab === 'permissions' }" role="tabpanel">
            <div class="row">
                @foreach ($permissions as $group => $groupPermissions)
                    <div class="col-xl-4 col-md-6">
                        <div class="card">
                            <div class="card-header"><h5 class="card-title mb-0">{{ $group }}</h5></div>
                            <div class="card-body">
                                <ul class="list-unstyled vstack gap-2 mb-0">
                                    @foreach ($groupPermissions as $permission)
                                        <li @class(['d-flex align-items-start gap-2', 'text-body' => $permission['granted']])>
                                            <i @class(['fs-16 lh-1', 'ri-checkbox-circle-fill text-success' => $permission['granted'], 'ri-indeterminate-circle-line text-muted' => ! $permission['granted']]) aria-hidden="true"></i>
                                            <span>{{ $permission['label'] }}<span class="visually-hidden">{{ $permission['granted'] ? ' (diizinkan)' : ' (tidak diizinkan)' }}</span></span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
