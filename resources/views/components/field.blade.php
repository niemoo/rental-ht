@props(['label', 'for', 'error' => null, 'hint' => null])

<div {{ $attributes }}>
    <label for="{{ $for }}" class="form-label">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif
    @if ($error)
        @error($error)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @endif
</div>
