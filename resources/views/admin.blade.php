<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EBOLO Admin</title>
    @vite(['resources/css/admin.css', 'resources/js/admin/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
