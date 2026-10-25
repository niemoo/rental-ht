{{-- One-off confirmation set with session()->flash('status', …) before a redirect. --}}
@if (session('status'))
    <div x-data="{ show: true }" x-show="show" role="status" class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-2.5 text-sm shadow-xs">
        <span>{{ session('status') }}</span>
        <button type="button" x-on:click="show = false" class="text-muted-foreground hover:text-foreground" aria-label="Tutup">
            <x-icon name="x" />
        </button>
    </div>
@endif
