<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Smart-PMIS') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    <div class="flex min-h-screen">
        <x-layouts.sidebar />
        <div class="flex-1 min-h-screen flex flex-col">
            <x-layouts.topbar :title="$title ?? null" />
            <main class="flex-1 bg-[#FAF5FF]">
                <div class="w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-8 lg:py-10">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
