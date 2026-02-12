<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#09090b">
    <title>{{ config('app.name', 'Gitty') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|jetbrains-mono:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    <script>
        // Force dark mode regardless of system preference
        if (window.Flux && window.Flux.applyAppearance) {
            window.Flux.applyAppearance('dark');
        }
    </script>
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    {{ $slot }}
    
    @livewireScripts
    @fluxScripts
</body>
</html>
