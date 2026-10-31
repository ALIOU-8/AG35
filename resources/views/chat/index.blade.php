<x-app-layout title="Messages" :full-width="true">
    <div x-data="chat(@js(['conversations' => $conversations, 'active' => $active, 'me' => auth()->user()->toPublicArray()]))"
         class="max-w-6xl mx-auto md:px-6 md:py-4 lg:px-8">
        <div class="flex h-[calc(100dvh-8.75rem)] overflow-hidden bg-white md:h-[calc(100dvh-5.75rem)] md:rounded-2xl md:shadow-sm md:ring-1 md:ring-gray-900/5">

            {{-- Liste des conversations --}}
            <aside class="{{ $active ? 'hidden md:flex' : 'flex' }} w-full flex-col border-r border-gray-100 md:w-80 md:shrink-0">
                <div class="border-b border-gray-100 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <h1 class="px-1 text-xl font-extrabold">Messages</h1>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('members.index') }}" class="rounded-full p-2 text-gray-500 hover:bg-gray-100" title="Écrire à un membre"><x-icon name="user-plus" /></a>
                            <button type="button" @click="$dispatch('open-modal', 'new-group')" class="rounded-full p-2 text-gray-500 hover:bg-gray-100" title="Nouveau groupe"><x-icon name="users" /></button>
                        </div>
                    </div>
                    <label class="relative mt-2 block">
                        <span class="sr-only">Rechercher une conversation</span>
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input type="search" x-model="search" placeholder="Rechercher…" class="w-full rounded-full border-0 bg-gray-100 py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-brand-500">
                    </label>
                </div>

                @if (session('status') || session('error'))
                    <div class="px-3 pt-3"><x-flash /></div>
                @endif

                <nav class="flex-1 overflow-y-auto p-2">
                    <template x-for="conversation in filteredConversations" :key="conversation.uuid">
                        <a :href="conversation.url"
                           class="flex items-center gap-3 rounded-xl p-2.5 transition"
                           :class="active && active.uuid === conversation.uuid ? 'bg-brand-50' : 'hover:bg-gray-50'">
                            <span class="relative h-12 w-12 shrink-0">
                                <template x-if="conversation.avatar">
                                    <img :src="conversation.avatar" alt="" class="h-12 w-12 rounded-full object-cover">
                                </template>
                                <template x-if="! conversation.avatar">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full text-sm font-semibold text-white"
                                          :class="conversation.type === 'direct' ? 'bg-gradient-to-br from-brand-400 to-brand-700' : 'bg-gradient-to-br from-yellow-400 to-guinea-red'"
                                          x-text="conversation.initials"></span>
                                </template>
                                <span x-show="conversation.online" class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 ring-2 ring-white"></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm" :class="conversation.unread ? 'font-bold' : 'font-semibold'" x-text="conversation.name"></span>
                                    <span class="shrink-0 text-[11px]" :class="conversation.unread ? 'font-semibold text-brand-600' : 'text-gray-400'" x-text="conversation.time"></span>
                                </span>
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-xs" :class="conversation.unread ? 'font-semibold text-gray-900' : 'text-gray-500'" x-text="conversation.preview"></span>
                                    <span x-show="conversation.unread" x-text="conversation.unread > 99 ? '99+' : conversation.unread"
                                          class="min-w-[20px] shrink-0 rounded-full bg-brand-500 px-1.5 text-center text-[11px] font-bold leading-5 text-white"></span>
                                </span>
                            </span>
                        </a>
                    </template>
                    <p x-show="! filteredConversations.length" class="p-6 text-center text-sm text-gray-500">
                        <span x-show="search">Aucune conversation trouvée.</span>
                        <span x-show="! search">Aucune conversation. Écrivez à un membre depuis l'annuaire ou créez un groupe.</span>
                    </p>
                </nav>
            </aside>

            {{-- Conversation ouverte --}}
            <section class="{{ $active ? 'flex' : 'hidden md:flex' }} min-w-0 flex-1 flex-col">
                @if ($active)
                    <header class="flex items-center gap-3 border-b border-gray-100 px-3 py-2.5">
                        <a href="{{ route('conversations.index') }}" class="rounded-full p-1.5 text-gray-500 hover:bg-gray-100 md:hidden" aria-label="Retour"><x-icon name="arrow-left" /></a>
                        @if ($active['avatar'])
                            <img src="{{ $active['avatar'] }}" alt="" class="h-10 w-10 rounded-full object-cover">
                        @else
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white {{ $active['type'] === 'direct' ? 'bg-gradient-to-br from-brand-400 to-brand-700' : 'bg-gradient-to-br from-yellow-400 to-guinea-red' }}">{{ $active['initials'] }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            @if ($active['profileUrl'])
                                <a href="{{ $active['profileUrl'] }}" class="block truncate font-semibold hover:underline">{{ $active['name'] }}</a>
                            @else
                                <div class="truncate font-semibold">{{ $active['name'] }}</div>
                            @endif
                            <div class="truncate text-xs text-gray-500">
                                <span x-show="typing" x-text="typing" class="text-brand-600"></span>
                                <span x-show="! typing">{{ $active['subtitle'] }}</span>
                            </div>
                        </div>
                        @if ($active['type'] !== 'direct')
                            <x-dropdown align="right" width="w-64">
                                <x-slot name="trigger">
                                    <button type="button" class="rounded-full p-2 text-gray-500 hover:bg-gray-100" aria-label="Participants"><x-icon name="users" /></button>
                                </x-slot>
                                <x-slot name="content">
                                    <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ count($active['participants']) }} participants</div>
                                    <ul class="max-h-64 overflow-y-auto px-4 pb-2 text-sm">
                                        @foreach ($active['participants'] as $participant)
                                            <li class="truncate py-1">{{ $participant }}</li>
                                        @endforeach
                                    </ul>
                                    @if ($active['urls']['leave'])
                                        <form method="POST" action="{{ $active['urls']['leave'] }}" class="border-t border-gray-100" onsubmit="return confirm('Quitter ce groupe ?')">
                                            @csrf
                                            <button class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-red-700 hover:bg-red-50"><x-icon name="logout" class="h-4 w-4" /> Quitter le groupe</button>
                                        </form>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        @endif
                    </header>

                    <div x-ref="scroller" @scroll.passive="onScroll" class="flex-1 overflow-y-auto bg-gray-50 px-3 py-4 sm:px-5">
                        <div class="mb-3 text-center" x-show="hasMore">
                            <button type="button" @click="loadOlder" :disabled="loadingOlder" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-600 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                                <span x-text="loadingOlder ? 'Chargement…' : 'Messages précédents'"></span>
                            </button>
                        </div>

                        <p x-show="! messages.length" class="py-10 text-center text-sm text-gray-500">Aucun message. Dites bonjour 👋</p>

                        <template x-for="(item, index) in timeline" :key="item.message.uuid">
                            <div>
                                <div x-show="item.showDay" class="my-4 flex items-center gap-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                    <span class="h-px flex-1 bg-gray-200"></span><span x-text="item.message.day"></span><span class="h-px flex-1 bg-gray-200"></span>
                                </div>
                                <div class="flex items-end gap-2" :class="[item.mine ? 'justify-end' : 'justify-start', item.showAuthor ? 'mt-3' : 'mt-0.5']">
                                    <template x-if="! item.mine && isGroup">
                                        <span class="h-7 w-7 shrink-0">
                                            <template x-if="item.showAvatar && item.message.author.avatar"><img :src="item.message.author.avatar" alt="" class="h-7 w-7 rounded-full object-cover"></template>
                                            <template x-if="item.showAvatar && ! item.message.author.avatar">
                                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 text-[10px] font-semibold text-white" x-text="item.message.author.initials"></span>
                                            </template>
                                        </span>
                                    </template>
                                    <div class="max-w-[80%] sm:max-w-[70%]">
                                        <div x-show="item.showAuthor && ! item.mine && isGroup" class="mb-0.5 px-3 text-xs font-semibold text-gray-500" x-text="item.message.author.name"></div>
                                        <div class="rounded-2xl px-3 py-2 shadow-sm" :class="item.mine ? 'rounded-br-md bg-brand-500 text-white' : 'rounded-bl-md bg-white text-gray-900 ring-1 ring-gray-900/5'">
                                            <template x-if="item.message.attachment && item.message.attachment.isImage">
                                                <a :href="item.message.attachment.url" target="_blank" rel="noopener" class="-mx-1 mb-1 block">
                                                    <img :src="item.message.attachment.url" :alt="item.message.attachment.name" class="max-h-64 rounded-xl object-cover" @load="keepAtBottom">
                                                </a>
                                            </template>
                                            <template x-if="item.message.attachment && ! item.message.attachment.isImage">
                                                <a :href="item.message.attachment.url" target="_blank" rel="noopener"
                                                   class="mb-1 flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm font-medium" :class="item.mine ? 'bg-white/15 hover:bg-white/25' : 'bg-gray-100 hover:bg-gray-200'">
                                                    <x-icon name="paperclip" class="h-4 w-4" /><span class="truncate" x-text="item.message.attachment.name"></span>
                                                </a>
                                            </template>
                                            <p x-show="item.message.body" class="whitespace-pre-line break-words text-[15px] leading-snug" x-text="item.message.body"></p>
                                            <div class="mt-0.5 text-right text-[10px]" :class="item.mine ? 'text-white/70' : 'text-gray-400'" x-text="item.message.time"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <form @submit.prevent="send" class="border-t border-gray-100 p-2 sm:p-3">
                        <div x-cloak x-show="file" class="mb-2 flex items-center gap-2 rounded-xl bg-gray-100 px-3 py-2 text-sm">
                            <x-icon name="paperclip" class="h-4 w-4 text-gray-500" />
                            <span class="flex-1 truncate" x-text="file?.name"></span>
                            <button type="button" @click="clearFile" class="text-gray-500 hover:text-gray-900" aria-label="Retirer la pièce jointe"><x-icon name="x" class="h-4 w-4" /></button>
                        </div>
                        <p x-cloak x-show="error" x-text="error" class="mb-2 px-1 text-xs text-red-600"></p>
                        <div class="flex items-end gap-2">
                            <label class="cursor-pointer rounded-full p-2.5 text-gray-500 hover:bg-gray-100" title="Joindre un fichier">
                                <x-icon name="paperclip" />
                                <input type="file" x-ref="file" class="sr-only" @change="file = $event.target.files[0] || null"
                                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.txt,.zip,.mp3,.m4a">
                            </label>
                            <textarea x-ref="input" x-model="draft" rows="1" maxlength="5000" placeholder="Votre message…"
                                      @keydown.enter="if (! $event.shiftKey && ! isTouch) { $event.preventDefault(); send(); }"
                                      @input="autosize($el); notifyTyping()"
                                      class="max-h-40 flex-1 resize-none rounded-2xl border-0 bg-gray-100 px-4 py-2.5 text-[15px] focus:ring-2 focus:ring-brand-500"></textarea>
                            <button type="submit" :disabled="sending || (! draft.trim() && ! file)"
                                    class="rounded-full bg-brand-500 p-2.5 text-white hover:bg-brand-600 disabled:opacity-40" aria-label="Envoyer">
                                <x-icon name="send" />
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-1 flex-col items-center justify-center p-10 text-center">
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600"><x-icon name="chat" class="h-8 w-8" /></span>
                        <h2 class="mt-4 text-lg font-bold">Vos messages</h2>
                        <p class="mt-1 max-w-sm text-sm text-gray-500">Choisissez une conversation, écrivez à un membre depuis son profil ou créez un groupe d'entraide.</p>
                        <button type="button" @click="$dispatch('open-modal', 'new-group')" class="mt-5 btn-primary"><x-icon name="plus" class="h-4 w-4" /> Nouveau groupe</button>
                    </div>
                @endif
            </section>
        </div>

        <x-modal name="new-group" maxWidth="md" :show="$errors->has('name') || $errors->has('members')" focusable>
            <form method="POST" action="{{ route('conversations.store') }}" class="p-6" x-data="{ filter: '', chosen: [] }">
                @csrf
                <h2 class="text-lg font-bold">Nouveau groupe</h2>
                <p class="mt-1 text-sm text-gray-500">Révisions, colocation, covoiturage, projet associatif…</p>

                <div class="mt-4">
                    <label for="group-name" class="label">Nom du groupe</label>
                    <input id="group-name" name="name" type="text" maxlength="80" required class="input" value="{{ old('name') }}" placeholder="Ex. Révisions L3 Informatique">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <span class="label">Membres</span>
                        <span class="text-xs text-gray-500" x-text="chosen.length + ' sélectionné(s)'"></span>
                    </div>
                    <input type="search" x-model="filter" placeholder="Filtrer les membres…" class="input">
                    <div class="mt-2 max-h-60 overflow-y-auto rounded-xl ring-1 ring-gray-200">
                        @foreach ($contacts as $contact)
                            <label x-show="@js(Str::lower($contact['name'])).includes(filter.toLowerCase())" class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-gray-50">
                                <input type="checkbox" name="members[]" value="{{ $contact['key'] }}" x-model="chosen" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                {{ $contact['name'] }}
                            </label>
                        @endforeach
                        @if ($contacts->isEmpty())
                            <p class="p-3 text-sm text-gray-500">Aucun autre membre actif pour le moment.</p>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('members')" class="mt-1" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'new-group')">Annuler</button>
                    <button class="btn-primary" :disabled="! chosen.length">Créer le groupe</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
