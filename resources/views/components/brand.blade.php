@props(['compact' => false])

{{-- App mark: the KRC logo. compact: the smaller size for Velzon's collapsed (icon-only) sidebar. --}}
<span {{ $attributes->class(['brand-mark d-inline-flex align-items-center align-middle']) }}>
    <img
        src="{{ asset('images/krc-logo.png') }}"
        alt="{{ config('invoice.business.name') }}"
        style="height: {{ $compact ? '26px' : '42px' }}; width: auto"
    >
</span>
