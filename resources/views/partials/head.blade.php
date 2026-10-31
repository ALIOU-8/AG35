<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#009460">
@if (config('webpush.vapid.public_key'))
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
@endif
<meta name="description" content="AG35 — Association des Étudiants Guinéens de Rennes : actualités, entraide, messagerie et réunions en visio entre membres.">

<title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'AG35') }}</title>

<!-- Application installable (PWA) -->
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="64x64" href="/icons/favicon-64.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="AG35">
<meta name="apple-mobile-web-app-status-bar-style" content="default">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
