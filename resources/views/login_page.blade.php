<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> SW-360 Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=1">
    @livewireStyles
</head>

<body>
    <livewire:login-component />
    @livewireScripts
</body>

</html>