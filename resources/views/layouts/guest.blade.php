<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        @include('partials.head')
    </head>
    <body>
        {{ $slot }}

        @livewireScriptConfig
    </body>
</html>
