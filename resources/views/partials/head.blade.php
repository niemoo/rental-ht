<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ filled($title ?? null) ? $title.' · ' : '' }}{{ config('invoice.business.name') }}</title>
<meta name="description" content="Pencatat penyewaan HT">

{{-- Apply the saved theme before first paint to avoid a light/dark flash. --}}
<script>
    try {
        if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
    } catch (e) {}
</script>

@fonts
@include('partials.velzon-styles')
{{-- Velzon's select widget (Choices.js); <x-select> starts it through the choicesSelect Alpine component in app.js. --}}
<script src="{{ asset('velzon/js/choices.min.js') }}" defer></script>
{{-- Velzon's dialogs (SweetAlert2); the confirmAction Alpine component in app.js opens them before saving. --}}
<script src="{{ asset('velzon/js/sweetalert2.min.js') }}" defer></script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
