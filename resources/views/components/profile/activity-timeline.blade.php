@props(['items', 'canViewRentals'])

{{-- Velzon activity timeline for the profile page. $items come from App\Livewire\Profile::recentActivities(). --}}
@if ($items->isEmpty())
    <x-empty-state>Belum ada penyewaan atau pembayaran yang dicatat akun ini.</x-empty-state>
@else
    <div class="acitivity-timeline">
        @foreach ($items as $activity)
            <div @class(['acitivity-item d-flex', 'py-3' => ! $loop->first && ! $loop->last, 'pt-3' => $loop->last && ! $loop->first])>
                <div class="flex-shrink-0">
                    <div class="avatar-xs acitivity-avatar">
                        <div class="avatar-title rounded-circle bg-{{ $activity['tone'] }}-subtle text-{{ $activity['tone'] }}"><x-icon :name="$activity['icon']" /></div>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3" style="min-width: 0">
                    <h6 class="mb-1">
                        <x-link-if :href="route('rentals.show', $activity['rental'])" :when="$canViewRentals" class="text-body" link-class="link-primary">{{ $activity['title'] }}</x-link-if>
                    </h6>
                    @if ($activity['detail'] !== '')
                        <p class="text-muted mb-2">{{ $activity['detail'] }}</p>
                    @endif
                    <small class="mb-0 text-muted tabular-nums">{{ $activity['at']->translatedFormat('j M Y, H:i') }} · {{ $activity['at']->diffForHumans() }}</small>
                </div>
            </div>
        @endforeach
    </div>
@endif
