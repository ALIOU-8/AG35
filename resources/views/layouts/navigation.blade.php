@php
    $links = [
        ['feed', 'home', "Fil d'actualité", 'feed'],
        ['conversations.index', 'chat', 'Messages', 'conversations.*'],
        ['meetings.index', 'video', 'Réunions', 'meetings.*'],
        ['members.index', 'users', 'Membres', 'members.*'],
    ];
@endphp

<nav class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200">
    <div class="flag-stripe h-1"></div>
    <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between gap-4">
            <a href="{{ route('feed') }}" class="shrink-0"><x-logo /></a>

            <div class="hidden md:flex items-center gap-1">
                @foreach ($links as [$route, $icon, $label, $pattern])
                    <a href="{{ route($route) }}"
                       class="relative inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs($pattern) ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <x-icon :name="$icon" />
                        {{ $label }}
                        @if ($route === 'conversations.index')
                            <span x-cloak x-show="unread > 0" x-text="unread > 99 ? '99+' : unread"
                                  class="min-w-[20px] rounded-full bg-guinea-red px-1.5 text-center text-[11px] font-bold leading-5 text-white"></span>
                        @endif
                    </a>
                @endforeach
                @can('manage-members')
                    <a href="{{ route('admin.members.index') }}"
                       class="relative inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <x-icon name="cog" />
                        Gestion
                        @if ($pendingMembers > 0)
                            <span class="min-w-[20px] rounded-full bg-guinea-yellow px-1.5 text-center text-[11px] font-bold leading-5 text-gray-900">{{ $pendingMembers }}</span>
                        @endif
                    </a>
                @endcan
            </div>

            <div class="flex items-center gap-1">
            @if (auth()->user()->isActive())
                <div class="relative" x-data="notificationBell(@js(['listUrl' => route('notifications.index'), 'readAllUrl' => route('notifications.read-all')]))"
                     @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button" @click="toggle" class="relative rounded-full p-2 transition" aria-label="Notifications"
                            :class="open ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100'">
                        <x-icon name="bell" class="h-6 w-6" />
                        <span x-cloak x-show="unreadNotifications > 0" x-text="unreadNotifications > 99 ? '99+' : unreadNotifications"
                              class="absolute -right-0.5 -top-0.5 min-w-[18px] rounded-full bg-guinea-red px-1 text-center text-[10px] font-bold leading-[18px] text-white ring-2 ring-white"></span>
                    </button>

                    <div x-cloak x-show="open" x-transition.origin.top.right
                         class="absolute -right-12 z-50 mt-2 w-96 max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-900/10 sm:right-0">
                        <div class="flex items-center justify-between px-4 pb-2 pt-3">
                            <h2 class="text-lg font-extrabold">Notifications</h2>
                            <button type="button" x-show="unreadNotifications > 0" @click="readAll" class="text-sm font-semibold text-brand-600 hover:underline">Tout marquer comme lu</button>
                        </div>

                        <div class="max-h-[70vh] overflow-y-auto px-2 pb-2">
                            <p x-show="loading && ! items.length" class="p-6 text-center text-sm text-gray-500">Chargement…</p>
                            <p x-show="error" x-text="error" class="p-4 text-center text-sm text-red-600"></p>
                            <div x-show="loaded && ! items.length" class="p-8 text-center">
                                <x-icon name="bell" class="mx-auto h-10 w-10 text-gray-300" />
                                <p class="mt-2 text-sm text-gray-500">Aucune notification pour le moment.</p>
                            </div>

                            <template x-for="item in items" :key="item.id">
                                <a :href="item.url" class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-gray-100" :class="! item.read && 'bg-brand-50/60'">
                                    <span class="relative shrink-0">
                                        <template x-if="item.actor.avatar"><img :src="item.actor.avatar" alt="" class="h-14 w-14 rounded-full object-cover"></template>
                                        <template x-if="! item.actor.avatar">
                                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 text-lg font-semibold text-white" x-text="item.actor.initials"></span>
                                        </template>
                                        @include('partials.notification-badge', ['kind' => 'item.kind'])
                                    </span>
                                    <span class="min-w-0 flex-1 text-sm leading-snug">
                                        <span class="line-clamp-3 text-gray-800">
                                            <span class="font-bold text-gray-900" x-text="item.actor.name"></span>
                                            <span x-text="item.text"></span><template x-if="item.excerpt"><span class="text-gray-600"> : « <span x-text="item.excerpt"></span> »</span></template>
                                        </span>
                                        <span class="mt-0.5 block text-xs" :class="item.read ? 'text-gray-500' : 'font-semibold text-brand-600'" x-text="item.time"></span>
                                    </span>
                                    <span x-show="! item.read" class="h-3 w-3 shrink-0 rounded-full bg-brand-500"></span>
                                </a>
                            </template>
                        </div>

                        <a href="{{ route('notifications.index') }}" class="block border-t border-gray-100 py-2.5 text-center text-sm font-semibold text-brand-600 hover:bg-gray-50">Voir toutes les notifications</a>
                    </div>
                </div>
            @endif

            <x-dropdown align="right" width="w-56">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 rounded-full p-0.5 pr-2 hover:bg-gray-100 transition">
                        <x-avatar :user="auth()->user()" size="h-9 w-9" />
                        <span class="hidden lg:block text-sm font-medium text-gray-700 max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <div class="text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-500">{{ auth()->user()->bureau_title ?? auth()->user()->roleLabel() }}</div>
                    </div>
                    <x-dropdown-link :href="route('members.show', auth()->user())">Mon profil</x-dropdown-link>
                    <x-dropdown-link :href="route('profile.edit')">Modifier mon profil</x-dropdown-link>
                    @if (auth()->user()->isActive())
                        <x-dropdown-link :href="route('dues.show')">Ma cotisation</x-dropdown-link>
                    @endif
                    @can('manage-members')
                        <x-dropdown-link :href="route('admin.members.index')" class="md:hidden">Gestion des membres</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.contacts.index')">Messages du site</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.settings.edit')">Contenu du site</x-dropdown-link>
                    @endcan
                    <x-dropdown-link :href="route('home')">Voir le site public</x-dropdown-link>
                    <div class="border-t border-gray-100"></div>
                    <button type="button" x-cloak x-show="$store.notifications.canAsk" @click="$store.notifications.requestPermission()"
                            class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-brand-700 hover:bg-gray-100">
                        <x-icon name="bell" class="h-4 w-4" /> Activer les notifications
                    </button>
                    <button type="button" @click.stop="$store.notifications.toggleSound()"
                            class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100">
                        <x-icon name="bell" class="h-4 w-4" />
                        <span x-text="$store.notifications.sound ? 'Son des notifications : activé' : 'Son des notifications : coupé'">Son des notifications</span>
                    </button>
                    <button type="button" x-cloak x-show="$store.pwa.canInstall" @click="$store.pwa.install()"
                            class="block w-full px-4 py-2 text-start text-sm leading-5 text-brand-700 hover:bg-gray-100">
                        Installer l'application
                    </button>
                    <button type="button" x-cloak x-show="$store.pwa.ios && ! $store.pwa.standalone" @click="$store.pwa.showHelp()"
                            class="block w-full px-4 py-2 text-start text-sm leading-5 text-brand-700 hover:bg-gray-100">
                        Installer sur l'iPhone
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            Se déconnecter
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
            </div>
        </div>
    </div>
</nav>
