@props(['options', 'active', 'counts', 'model' => 'filter'])

{{-- $options: [key => label]; $counts: [key => int]. Clicking a tab sets the Livewire property $model. --}}
<ul {{ $attributes->class(['nav nav-pills nav-custom-light flex-nowrap overflow-x-auto gap-1']) }} role="tablist">
    @foreach ($options as $key => $label)
        {{-- Numeric keys (e.g. ids) become ints in PHP arrays, while the Livewire property is a string. --}}
        @php($isActive = (string) $active === (string) $key)
        <li class="nav-item" wire:key="tab-{{ $model }}-{{ $key }}">
            <button type="button" wire:click="$set('{{ $model }}', '{{ $key }}')" @class(['nav-link py-1 px-3 fs-13 text-nowrap', 'active' => $isActive])>
                {{ $label }}
                <span @class(['badge ms-1 tabular-nums', 'bg-white text-primary' => $isActive, 'bg-light text-muted' => ! $isActive])>{{ $counts[$key] ?? 0 }}</span>
            </button>
        </li>
    @endforeach
</ul>
