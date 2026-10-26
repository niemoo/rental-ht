{{-- One-off confirmation set with session()->flash('status', …) before a redirect, shown as a Velzon toast. --}}
@if (session('status'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 6000)"
        role="status"
        class="toast show align-items-center text-bg-success border-0 position-fixed end-0 m-3"
        style="top: calc(70px + env(safe-area-inset-top, 0px)); z-index: 1090"
    >
        <div class="d-flex">
            <div class="toast-body">{{ session('status') }}</div>
            <button type="button" x-on:click="show = false" class="btn-close btn-close-white me-2 m-auto" aria-label="Tutup"></button>
        </div>
    </div>
@endif
