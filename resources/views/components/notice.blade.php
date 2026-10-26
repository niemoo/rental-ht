@props(['message', 'property' => 'notice'])

{{-- Dismissible feedback for a Livewire action that stays on the page (e.g. after a delete). --}}
@if ($message)
    <div role="status" {{ $attributes->class(['alert alert-primary alert-border-left d-flex align-items-center justify-content-between gap-3 mb-0']) }}>
        <span>{{ $message }}</span>
        <button type="button" wire:click="$set('{{ $property }}', null)" class="btn-close" aria-label="Tutup"></button>
    </div>
@endif
