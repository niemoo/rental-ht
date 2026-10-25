<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ filled($title ?? null) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
<meta name="description" content="Pencatat penyewaan HT">

{{-- Apply the saved theme before first paint to avoid a light/dark flash. --}}
<script>
    try {
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
    } catch (e) {}
</script>

@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
