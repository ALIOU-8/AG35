<x-app-layout title="Fil d'actualité">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            {{-- Nouvelle publication --}}
            <form x-data="postComposer(@js(['url' => route('posts.store')]))" @submit.prevent="submit"
                  class="card p-4" enctype="multipart/form-data">
                <div class="flex gap-3">
                    <x-avatar :user="auth()->user()" />
                    <textarea x-model="body" rows="2" maxlength="5000" name="body"
                              placeholder="Quoi de neuf, {{ Str::before(auth()->user()->name, ' ') }} ?"
                              @focus="expanded = true"
                              @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                              class="input min-h-[3rem] resize-none border-0 bg-gray-100 shadow-none focus:bg-white"></textarea>
                </div>

                <div x-cloak x-show="files.length" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
                    <template x-for="(file, index) in files" :key="file.key">
                        <div class="relative aspect-square overflow-hidden rounded-xl bg-gray-100">
                            <template x-if="file.isVideo">
                                <video :src="file.preview" muted playsinline class="h-full w-full object-cover"></video>
                            </template>
                            <template x-if="! file.isVideo">
                                <img :src="file.preview" alt="" class="h-full w-full object-cover">
                            </template>
                            <span x-show="file.isVideo" class="absolute left-1.5 top-1.5 rounded-md bg-black/60 px-1.5 py-0.5 text-[10px] font-semibold text-white">Vidéo</span>
                            <button type="button" @click="remove(index)" :disabled="uploading"
                                    class="absolute right-1.5 top-1.5 rounded-full bg-black/60 p-1 text-white hover:bg-black/80" aria-label="Retirer">
                                <x-icon name="x" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </template>
                </div>

                <div x-cloak x-show="uploading" class="mt-3">
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-brand-500 transition-all" :style="`width: ${progress}%`"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500" x-text="progress < 100 ? `Envoi en cours… ${progress} %` : 'Traitement…'"></p>
                </div>

                <ul x-cloak x-show="errors.length" class="mt-3 space-y-1 text-sm text-red-600">
                    <template x-for="message in errors" :key="message"><li x-text="message"></li></template>
                </ul>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3">
                    <div class="flex items-center gap-1">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">
                            <x-icon name="photo" class="text-brand-600" />
                            <span>Photo / vidéo</span>
                            <input type="file" multiple class="sr-only" x-ref="media" @change="add($event)"
                                   accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime">
                        </label>
                        @can('manage-members')
                            <select x-model="visibility" class="rounded-xl border-0 bg-gray-100 py-2 pl-3 pr-8 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500" aria-label="Visibilité">
                                <option value="members">Membres uniquement</option>
                                <option value="public">Public (site de l'association)</option>
                            </select>
                        @endcan
                    </div>
                    <button type="submit" class="btn-primary" :disabled="uploading || (! body.trim() && ! files.length)">
                        <span x-text="uploading ? 'Publication…' : 'Publier'"></span>
                    </button>
                </div>
            </form>

            @forelse ($posts as $post)
                <x-post-card :post="$post" />
            @empty
                <div class="card p-10 text-center">
                    <x-icon name="photo" class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 font-semibold">Aucune publication pour le moment</p>
                    <p class="mt-1 text-sm text-gray-500">Partagez la première nouvelle avec la communauté !</p>
                </div>
            @endforelse

            {{ $posts->links() }}
        </div>

        <aside class="space-y-4">
            <section class="card p-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Réunions à venir</h2>
                    <a href="{{ route('meetings.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Tout voir</a>
                </div>
                <ul class="mt-3 space-y-2">
                    @forelse ($meetings as $meeting)
                        <li>
                            <a href="{{ route('meetings.show', $meeting) }}" class="flex items-center gap-3 rounded-xl p-2 hover:bg-gray-50">
                                <span class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-xl {{ $meeting->status() === 'open' ? 'bg-guinea-red/10 text-guinea-red' : 'bg-brand-50 text-brand-700' }}">
                                    @if ($meeting->scheduled_at)
                                        <span class="text-[10px] font-semibold uppercase leading-none">{{ $meeting->scheduled_at->translatedFormat('M') }}</span>
                                        <span class="text-sm font-bold leading-tight">{{ $meeting->scheduled_at->format('j') }}</span>
                                    @else
                                        <x-icon name="video" />
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold">{{ $meeting->title }}</span>
                                    <span class="block text-xs text-gray-500">
                                        {{ $meeting->status() === 'open' ? 'En cours · rejoindre' : $meeting->scheduled_at->translatedFormat('l j F à H:i') }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500">Aucune réunion prévue.</li>
                    @endforelse
                </ul>
            </section>

            @if ($newMembers->isNotEmpty())
                <section class="card p-4">
                    <h2 class="font-bold">Nouveaux membres</h2>
                    <ul class="mt-3 space-y-1">
                        @foreach ($newMembers as $member)
                            <li>
                                <a href="{{ route('members.show', $member) }}" class="flex items-center gap-3 rounded-xl p-2 hover:bg-gray-50">
                                    <x-avatar :user="$member" size="h-9 w-9" online />
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold">{{ $member->name }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ $member->school ?? $member->studies() }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('members.index') }}" class="mt-2 block text-center text-sm font-semibold text-brand-600 hover:underline">Annuaire des membres</a>
                </section>
            @endif

            <section class="card overflow-hidden">
                <div class="flag-stripe h-1"></div>
                <div class="p-4 text-sm text-gray-600">
                    <h2 class="font-bold text-gray-900">Besoin d'aide ?</h2>
                    <p class="mt-1">Logement, titre de séjour, inscription, CAF : posez votre question dans la discussion générale ou écrivez directement à un membre du bureau.</p>
                    <a href="{{ route('conversations.index') }}" class="mt-3 btn-secondary w-full"><x-icon name="chat" class="h-4 w-4" /> Ouvrir la messagerie</a>
                </div>
            </section>
        </aside>
    </div>
</x-app-layout>
