<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Issues Board') }}</title>

    @vite('resources/js/app.jsx') {{-- Vite entry point --}}
</head>
<body class="antialiased">
    <div id="root"></div> {{-- React will mount here --}}
</body>
</html>
