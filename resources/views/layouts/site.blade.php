<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')
    </head>
    <body class="font-sans antialiased bg-white text-gray-900">
        @php
            $siteLinks = [
                [route('home').'#association', "L'association"],
                [route('news.index'), 'Actualités'],
                [route('home').'#bureau', 'Le bureau'],
                [route('home').'#adhesion', 'Adhérer'],
                [route('home').'#contact', 'Contact'],
            ];
            $socials = array_filter([
                'Facebook' => $site['facebook_url'],
                'Instagram' => $site['instagram_url'],
                'WhatsApp' => $site['whatsapp_url'],
            ]);
        @endphp

        <header x-data="{ open: false }" class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-100">
            <div class="flag-stripe h-1"></div>
            <div class="max-w-6xl mx-auto flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="shrink-0"><x-logo /></a>

                <nav class="hidden lg:flex items-center gap-1 text-sm font-medium text-gray-600">
                    @foreach ($siteLinks as [$href, $label])
                        <a href="{{ $href }}" class="rounded-xl px-3 py-2 hover:bg-gray-100 hover:text-gray-900 transition">{{ $label }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('feed') }}" class="btn-primary">Espace membres</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex btn-secondary">Se connecter</a>
                        <a href="{{ route('register') }}" class="btn-primary">Rejoindre</a>
                    @endauth
                    <button type="button" @click="open = ! open" class="lg:hidden rounded-xl p-2 text-gray-600 hover:bg-gray-100" aria-label="Menu">
                        <x-icon name="menu" class="h-6 w-6" x-show="! open" />
                        <x-icon name="x" class="h-6 w-6" x-show="open" x-cloak />
                    </button>
                </div>
            </div>

            <nav x-show="open" x-cloak x-transition @click="open = false" class="lg:hidden border-t border-gray-100 px-4 py-3">
                <div class="grid gap-1 text-sm font-medium">
                    @foreach ($siteLinks as [$href, $label])
                        <a href="{{ $href }}" class="rounded-xl px-3 py-2.5 hover:bg-gray-100">{{ $label }}</a>
                    @endforeach
                    @guest
                        <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 text-brand-700 hover:bg-gray-100">Se connecter</a>
                    @endguest
                </div>
            </nav>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="bg-gray-950 text-gray-300">
            <div class="flag-stripe h-1"></div>
            <div class="max-w-6xl mx-auto grid gap-10 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
                <div>
                    <x-logo dark />
                    <p class="mt-4 text-sm text-gray-400">{{ $site['slogan'] }}</p>
                </div>
                <div class="text-sm">
                    <h2 class="font-semibold text-white">Nous contacter</h2>
                    <ul class="mt-3 grid gap-2 text-gray-400">
                        @if ($site['contact_email'])
                            <li class="flex items-center gap-2"><x-icon name="envelope" class="h-4 w-4" /><a href="mailto:{{ $site['contact_email'] }}" class="hover:text-white">{{ $site['contact_email'] }}</a></li>
                        @endif
                        @if ($site['contact_phone'])
                            <li class="flex items-center gap-2"><x-icon name="phone" class="h-4 w-4" />{{ $site['contact_phone'] }}</li>
                        @endif
                        @if ($site['address'])
                            <li class="flex items-center gap-2"><x-icon name="map-pin" class="h-4 w-4" />{{ $site['address'] }}</li>
                        @endif
                    </ul>
                </div>
                <div class="text-sm">
                    <h2 class="font-semibold text-white">Suivre l'association</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-xl bg-white/10 px-3 py-1.5 hover:bg-white/20 hover:text-white">{{ $network }}</a>
                        @empty
                            <span class="text-gray-500">Réseaux sociaux bientôt disponibles.</span>
                        @endforelse
                    </div>
                    <a href="{{ route('login') }}" class="mt-5 inline-flex items-center gap-2 text-gray-400 hover:text-white">
                        <x-icon name="lock" class="h-4 w-4" /> Espace membres
                    </a>
                </div>
            </div>
            <div class="border-t border-white/10 py-5 text-center text-xs text-gray-500">
                © {{ now()->year }} {{ $site['association_name'] }}
            </div>
        </footer>
    </body>
</html>
