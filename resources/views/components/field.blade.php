@props(['label', 'for', 'error' => null, 'hint' => null])

<div {{ $attributes->class(['flex flex-col gap-1.5']) }}>
    <label for="{{ $for }}" class="text-sm leading-none font-medium select-none">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
    @if ($error)
        @error($error)
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
