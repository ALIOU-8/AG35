@props(['post', 'showAllComments' => false])

@php
    $viewer = auth()->user();
    $media = $post->media;
    $shownMedia = $media->take(4);
    $state = [
        'liked' => (bool) $post->liked_by_viewer,
        'likes' => (int) $post->likers_count,
        'comments' => $post->comments->map->toFeedArray($viewer)->values(),
        'likeUrl' => route('posts.like', $post),
        'commentUrl' => route('comments.store', $post),
        'images' => $media->reject->isVideo()->map(fn ($item) => $item->url())->values(),
        'showAll' => $showAllComments,
    ];
@endphp

<article {{ $attributes->merge(['class' => 'card']) }} x-data="postCard(@js($state))">
    <header class="flex items-start gap-3 p-4 pb-0">
        <a href="{{ route('members.show', $post->user) }}"><x-avatar :user="$post->user" /></a>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <a href="{{ route('members.show', $post->user) }}" class="font-semibold hover:underline">{{ $post->user->name }}</a>
                @if ($post->user->bureau_title)
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700">{{ $post->user->bureau_title }}</span>
                @endif
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <time datetime="{{ $post->created_at->toIso8601String() }}" title="{{ $post->created_at->translatedFormat('j F Y à H:i') }}">{{ $post->created_at->diffForHumans() }}</time>
                @if ($post->isPublic())
                    <span aria-hidden="true">·</span>
                    <span class="inline-flex items-center gap-1 text-brand-700"><x-icon name="globe" class="h-3.5 w-3.5" /> Public</span>
                @endif
            </div>
        </div>

        @if ($post->canBeDeletedBy($viewer))
            <x-dropdown align="right" width="w-48">
                <x-slot name="trigger">
                    <button type="button" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Options">
                        <x-icon name="dots" />
                    </button>
                </x-slot>
                <x-slot name="content">
                    <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('Supprimer cette publication ?')">
                        @csrf
                        @method('DELETE')
                        <button class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-red-700 hover:bg-red-50">
                            <x-icon name="trash" class="h-4 w-4" /> Supprimer
                        </button>
                    </form>
                </x-slot>
            </x-dropdown>
        @endif
    </header>

    @if ($post->body)
        <div class="px-4 pt-3" x-data="{ expanded: {{ Str::length($post->body) > 500 ? 'false' : 'true' }} }">
            <p class="whitespace-pre-line break-words text-[15px] leading-relaxed text-gray-800" :class="expanded ? '' : 'line-clamp-6'">{{ $post->body }}</p>
            <button type="button" x-show="! expanded" @click="expanded = true" class="mt-1 text-sm font-semibold text-gray-500 hover:text-gray-800">Voir plus</button>
        </div>
    @endif

    @if ($media->isNotEmpty())
        <div class="mt-3 grid gap-0.5 overflow-hidden bg-gray-100 {{ $media->count() > 1 ? 'grid-cols-2' : '' }}">
            @foreach ($shownMedia as $item)
                @php
                    $span = $media->count() === 3 && $loop->first ? 'col-span-2 aspect-[2/1]' : ($media->count() > 1 ? 'aspect-square' : '');
                    $imageIndex = $media->reject->isVideo()->values()->search(fn ($image) => $image->is($item));
                @endphp
                <div class="relative {{ $span }}">
                    @if ($item->isVideo())
                        <video src="{{ $item->url() }}" controls preload="metadata" playsinline class="h-full w-full bg-black {{ $media->count() > 1 ? 'object-cover' : 'max-h-[36rem]' }}"></video>
                    @elseif ($media->count() === 1)
                        {{-- Photo seule : affichée en entier, sur un fond flouté tiré de la photo. --}}
                        <button type="button" @click="open({{ $imageIndex }})" class="relative block w-full overflow-hidden bg-gray-900">
                            <img src="{{ $item->url() }}" alt="" aria-hidden="true" loading="lazy" class="absolute inset-0 h-full w-full scale-110 object-cover opacity-60 blur-2xl">
                            <img src="{{ $item->url() }}" alt="" loading="lazy" class="relative mx-auto block max-h-[36rem] w-auto max-w-full object-contain">
                        </button>
                    @else
                        <button type="button" @click="open({{ $imageIndex }})" class="block h-full w-full">
                            <img src="{{ $item->url() }}" alt="" loading="lazy" class="block h-full w-full object-cover">
                        </button>
                    @endif
                    @if ($loop->last && $media->count() > 4)
                        <button type="button" @click="open({{ $imageIndex === false ? 0 : $imageIndex }})"
                                class="absolute inset-0 flex items-center justify-center bg-black/55 text-3xl font-bold text-white">+{{ $media->count() - 4 }}</button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-between px-4 pt-3 text-xs text-gray-500">
        <span x-show="likes > 0" x-text="likes === 1 ? '1 j\'aime' : likes + ' j\'aime'"></span>
        <span x-show="likes === 0"></span>
        <button type="button" x-show="comments.length > 0" @click="showAll = true" class="hover:underline"
                x-text="comments.length === 1 ? '1 commentaire' : comments.length + ' commentaires'"></button>
    </div>

    <div class="mx-4 mt-2 grid grid-cols-2 border-y border-gray-100 py-1 text-sm font-semibold text-gray-600">
        <button type="button" @click="toggleLike" :disabled="busy" class="flex items-center justify-center gap-2 rounded-lg py-1.5 hover:bg-gray-50" :class="liked && 'text-guinea-red'">
            <svg class="h-5 w-5" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" :fill="liked ? 'currentColor' : 'none'" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
            </svg>
            J'aime
        </button>
        <button type="button" @click="$refs.comment.focus()" class="flex items-center justify-center gap-2 rounded-lg py-1.5 hover:bg-gray-50">
            <x-icon name="comment" /> Commenter
        </button>
    </div>

    <div id="commentaires" class="scroll-mt-20 space-y-3 p-4">
        <button type="button" x-cloak x-show="! showAll && comments.length > 3" @click="showAll = true" class="text-sm font-semibold text-gray-500 hover:text-gray-800"
                x-text="'Voir les ' + comments.length + ' commentaires'"></button>

        <template x-for="(comment, index) in visibleComments" :key="comment.key">
            <div class="flex items-start gap-2">
                <a :href="comment.author.url" class="shrink-0">
                    <template x-if="comment.author.avatar"><img :src="comment.author.avatar" alt="" class="h-8 w-8 rounded-full object-cover"></template>
                    <template x-if="! comment.author.avatar">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 text-xs font-semibold text-white" x-text="comment.author.initials"></span>
                    </template>
                </a>
                <div class="min-w-0">
                    <div class="rounded-2xl bg-gray-100 px-3 py-2">
                        <a :href="comment.author.url" class="text-sm font-semibold hover:underline" x-text="comment.author.name"></a>
                        <p class="whitespace-pre-line break-words text-sm text-gray-800" x-text="comment.body"></p>
                    </div>
                    <div class="mt-0.5 flex gap-3 px-3 text-xs text-gray-500">
                        <span x-text="comment.date"></span>
                        <button type="button" x-show="comment.deleteUrl" @click="removeComment(comment)" class="font-semibold hover:text-red-700">Supprimer</button>
                    </div>
                </div>
            </div>
        </template>

        <form @submit.prevent="addComment" class="flex items-end gap-2">
            <x-avatar :user="$viewer" size="h-8 w-8" text="text-xs" />
            <div class="flex flex-1 items-end rounded-2xl bg-gray-100 pr-1 focus-within:ring-2 focus-within:ring-brand-500">
                <textarea x-ref="comment" x-model="draft" rows="1" maxlength="2000" placeholder="Écrire un commentaire…"
                          @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); addComment(); }"
                          @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                          class="max-h-32 flex-1 resize-none border-0 bg-transparent px-3 py-2 text-sm focus:ring-0"></textarea>
                <button type="submit" :disabled="sending || ! draft.trim()" class="mb-1 rounded-full p-1.5 text-brand-600 hover:bg-brand-50 disabled:opacity-40" aria-label="Envoyer">
                    <x-icon name="send" class="h-4 w-4" />
                </button>
            </div>
        </form>
        <p x-cloak x-show="error" x-text="error" class="text-xs text-red-600"></p>
    </div>

    {{-- Visionneuse des photos --}}
    <template x-teleport="body">
        <div x-cloak x-show="viewer !== null" x-transition.opacity @keydown.escape.window="viewer = null"
             @keydown.arrow-right.window="next" @keydown.arrow-left.window="prev"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4" @click.self="viewer = null">
            <img :src="images[viewer]" alt="" class="max-h-full max-w-full rounded-lg object-contain">
            <button type="button" @click="viewer = null" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Fermer"><x-icon name="x" class="h-6 w-6" /></button>
            <template x-if="images.length > 1">
                <div>
                    <button type="button" @click="prev" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Précédente"><x-icon name="arrow-left" /></button>
                    <button type="button" @click="next" class="absolute right-3 top-1/2 -translate-y-1/2 rotate-180 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Suivante"><x-icon name="arrow-left" /></button>
                    <span class="absolute bottom-4 left-1/2 -translate-x-1/2 text-sm text-white/80" x-text="(viewer + 1) + ' / ' + images.length"></span>
                </div>
            </template>
        </div>
    </template>
</article>
