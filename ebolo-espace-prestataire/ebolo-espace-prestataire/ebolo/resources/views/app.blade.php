<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#310729">
    <title>{{ config('app.name', 'Ébolo') }} • Espace Prestataire</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
