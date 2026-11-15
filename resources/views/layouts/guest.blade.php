<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'AgendaGo') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans bg-petrol">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
            <a href="/" class="flex items-center gap-2.5 mb-8">
                <x-application-logo class="h-11 w-11" />
                <span class="text-white font-extrabold text-2xl tracking-tight">agenda<span class="text-turquoise-500">Go</span></span>
            </a>

            <div class="w-full sm:max-w-md bg-warm rounded-2xl shadow-card p-6 sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-white/50 text-xs">Tu negocio, organizado en un solo lugar.</p>
        </div>
    </body>
</html>
