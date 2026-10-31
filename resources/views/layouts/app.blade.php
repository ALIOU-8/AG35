<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900"
          @if (auth()->user()->isActive()) data-user-channel="user.{{ auth()->user()->uuid }}" data-push-url="{{ route('push-subscriptions.store') }}" @endif
          data-user-uuid="{{ auth()->user()->uuid }}"
          x-data="appShell({ unread: {{ (int) $unreadMessages }}, unreadNotifications: {{ (int) $unreadNotifications }} })">
        <div class="min-h-screen pb-20 md:pb-0">
            @include('layouts.navigation')

            @if (auth()->user()->isActive())
                <div x-cloak x-show="$store.notifications.showBanner" x-transition class="border-b border-brand-100 bg-brand-50">
                    <div class="max-w-6xl mx-auto flex items-center gap-3 px-3 py-2 text-sm text-brand-900 sm:px-6 lg:px-8">
                        <x-icon name="bell" class="text-brand-600" />
                        <span class="flex-1">Activez les notifications pour être alerté de chaque nouveau message, même quand l'application est en arrière-plan.</span>
                        <button type="button" @click="$store.notifications.requestPermission()" class="btn-primary shrink-0 px-3 py-1">Activer</button>
                        <button type="button" @click="$store.notifications.dismissBanner()" class="rounded-full p-1 text-brand-700 hover:bg-brand-100" aria-label="Plus tard">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            @endif

            {{-- Installation sur le téléphone : un seul bandeau à la fois, après celui des notifications --}}
            <div x-cloak x-show="$store.pwa.showBanner && ! $store.notifications.showBanner" x-transition class="border-b border-yellow-200 bg-yellow-50">
                <div class="max-w-6xl mx-auto flex items-center gap-3 px-3 py-2 text-sm text-gray-800 sm:px-6 lg:px-8">
                    <img src="/icons/icon-192.png" alt="" class="h-9 w-9 shrink-0 rounded-lg">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">Installez l'application AG35</span>
                        <span x-show="! $store.pwa.ios" class="block text-gray-600">Ouverture en un geste depuis l'écran d'accueil, notifications comprises.</span>
                        <span x-show="$store.pwa.ios" class="block text-gray-600">
                            Touchez <x-icon name="share" class="inline h-4 w-4 -mt-1 text-blue-600" /> <strong>Partager</strong>, puis <strong>« Sur l'écran d'accueil »</strong>.
                        </span>
                    </span>
                    <button type="button" x-show="$store.pwa.canInstall" @click="$store.pwa.install()" class="btn-primary shrink-0 px-3 py-1">Installer</button>
                    <button type="button" @click="$store.pwa.dismiss()" class="rounded-full p-1 text-gray-500 hover:bg-yellow-100" aria-label="Plus tard">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            @isset($header)
                <header class="bg-white shadow-sm">
                    <div class="max-w-6xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="{{ $fullWidth ? '' : 'max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 py-5' }}">
                @unless ($fullWidth)
                    <x-flash />
                @endunless
                {{ $slot }}
            </main>
        </div>

        {{-- Navigation basse, façon application mobile --}}
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-gray-200 pb-[env(safe-area-inset-bottom)]">
            <div class="grid grid-cols-5 text-[11px] font-medium text-gray-500">
                @php
                    $tabs = [
                        ['feed', 'home', 'Fil', 'feed'],
                        ['conversations.index', 'chat', 'Messages', 'conversations.*'],
                        ['meetings.index', 'video', 'Réunions', 'meetings.*'],
                        ['members.index', 'users', 'Membres', 'members.*'],
                        ['profile.edit', 'user', 'Profil', 'profile.*'],
                    ];
                @endphp
                @foreach ($tabs as [$route, $icon, $label, $pattern])
                    <a href="{{ route($route) }}" class="relative flex flex-col items-center gap-0.5 py-2 {{ request()->routeIs($pattern) ? 'text-brand-600' : '' }}">
                        <x-icon :name="$icon" class="h-6 w-6" />
                        {{ $label }}
                        @if ($route === 'conversations.index')
                            <span x-cloak x-show="unread > 0" x-text="unread > 99 ? '99+' : unread"
                                  class="absolute top-1 left-1/2 ml-1.5 min-w-[18px] rounded-full bg-guinea-red px-1 text-center text-[10px] font-bold leading-[18px] text-white"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </nav>

        {{-- Notifications des nouveaux messages : en haut à gauche, façon Facebook --}}
        <div class="pointer-events-none fixed left-3 top-[4.25rem] z-[70] flex w-[22rem] max-w-[calc(100vw-1.5rem)] flex-col gap-2" aria-live="polite">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.visible"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="-translate-x-[110%] opacity-0"
                     x-transition:enter-end="translate-x-0 opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-x-0 opacity-100"
                     x-transition:leave-end="-translate-x-[110%] opacity-0"
                     @mouseenter="hold(toast)" @mouseleave="release(toast)"
                     class="pointer-events-auto relative rounded-2xl bg-white shadow-xl ring-1 ring-gray-900/10">
                    <a :href="toast.url" @click="dismiss(toast)" class="flex items-center gap-3 rounded-2xl p-3 pr-9 hover:bg-gray-50">
                        <span class="relative shrink-0">
                            <template x-if="toast.avatar"><img :src="toast.avatar" alt="" class="h-14 w-14 rounded-full object-cover"></template>
                            <template x-if="! toast.avatar">
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 text-lg font-semibold text-white" x-text="toast.initials"></span>
                            </template>
                            @include('partials.notification-badge', ['kind' => 'toast.kind'])
                        </span>
                        <span class="min-w-0 text-sm leading-snug">
                            <span class="block font-bold text-gray-900" x-text="toast.title"></span>
                            <span class="line-clamp-2 break-words text-gray-700" x-text="toast.body"></span>
                            <span class="mt-0.5 block text-xs font-semibold text-brand-600">
                                <span x-text="toast.context"></span> · à l'instant
                            </span>
                        </span>
                    </a>
                    <button type="button" @click="dismiss(toast)" class="absolute right-2 top-2 rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </div>

        @stack('scripts')
    </body>
</html>
