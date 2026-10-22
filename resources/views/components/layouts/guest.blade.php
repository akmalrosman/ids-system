@props(['title' => null])

<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-950">
<head>
    @include('partials.head', ['title' => $title])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-neutral-100 antialiased">
    {{ $slot }}
    <x-ui.flash />
</body>
</html>
