@props(['options', 'active', 'counts', 'model' => 'filter'])

{{-- $options: [key => label]; $counts: [key => int]. Clicking a tab sets the Livewire property $model. --}}
<div {{ $attributes->class(['inline-flex gap-1 overflow-x-auto rounded-lg border border-border bg-card p-1 shadow-xs']) }}>
    @foreach ($options as $key => $label)
        {{-- Numeric keys (e.g. ids) become ints in PHP arrays, while the Livewire property is a string. --}}
        @php($isActive = (string) $active === (string) $key)
        <button
            type="button"
            wire:click="$set('{{ $model }}', '{{ $key }}')"
            wire:key="tab-{{ $model }}-{{ $key }}"
            @class([
                'rounded-md px-3 py-1.5 text-[13px] font-medium whitespace-nowrap transition-colors',
                'bg-primary text-primary-foreground' => $isActive,
                'text-muted-foreground hover:text-foreground' => ! $isActive,
            ])
        >
            {{ $label }}
            <span @class(['tabular-nums', 'text-primary-foreground/80' => $isActive, 'text-muted-foreground' => ! $isActive])>{{ $counts[$key] ?? 0 }}</span>
        </button>
    @endforeach
</div>
