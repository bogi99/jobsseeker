<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="description"
        content="JobRat - The best place to post and find jobs. Connect with top employers and discover your next career opportunity today! Simple and easy to use.">
    <meta name="author" content="JobRat">
    <link rel="canonical" href="{{ url()->current() }}" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <meta name="keywords" content="{{ $metaKeywords ?? 'Jobs, PHP, Python, JavaScript, MySQL, PostgreSQL, coding' }}">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>

        </style>
    @endif
</head>

<body>
    <div class="container mx-auto px-3">
        <div class="flex flex-col h-screen ">
            <x-topbar></x-topbar>
            <x-menubar></x-menubar>
            {{ $slot }}
            <x-footer></x-footer>
            <x-subfooter></x-subfooter>
        </div>
    </div>
</body>

</html>
