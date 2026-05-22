<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Sign in' }} | Avatech Smart-PMIS</title>
    @vite(['resources/css/app.css'])
    @filamentStyles
    <style>
        body {
            background-color: #FAF5FF;
            background-image:
                radial-gradient(circle at 15% 50%, rgba(168, 85, 247, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 85% 30%, rgba(192, 132, 252, 0.08) 0%, transparent 50%);
            background-attachment: fixed;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased text-[#1E1B4B]">
    {{ $slot }}
    @filamentScripts
</body>
</html>
