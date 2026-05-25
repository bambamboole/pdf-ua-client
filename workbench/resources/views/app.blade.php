<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>pdf-ua-client template builder</title>
    @vite(['workbench/resources/css/app.css', 'workbench/resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="bg-gray-100">
    @inertia
</body>
</html>
