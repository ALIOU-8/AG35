<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10 bg-gradient-to-br from-brand-50 via-white to-yellow-50">
            <a href="{{ route('home') }}" class="mb-6"><x-logo /></a>

            <div class="w-full sm:max-w-lg card overflow-hidden">
                <div class="flag-stripe h-1.5"></div>
                <div class="px-6 py-6 sm:px-8">
                    {{ $slot }}
                </div>
            </div>

            <a href="{{ route('home') }}" class="mt-6 text-sm text-gray-500 hover:text-gray-700">← Retour au site de l'association</a>
        </div>
    </body>
</html>
