@props(['message', 'property' => 'notice'])

{{-- Dismissible feedback for a Livewire action that stays on the page (e.g. after a delete). --}}
@if ($message)
    <div role="status" {{ $attributes->class(['flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-2.5 text-sm shadow-xs']) }}>
        <span>{{ $message }}</span>
        <button type="button" wire:click="$set('{{ $property }}', null)" class="text-muted-foreground hover:text-foreground" aria-label="Tutup">
            <x-icon name="x" />
        </button>
    </div>
@endif
