<x-site-layout :title="$post->headline()">
    <article class="max-w-3xl mx-auto px-4 py-10 sm:px-6 lg:py-14">
        <a href="{{ route('news.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900">
            <x-icon name="arrow-left" class="h-4 w-4" /> Toutes les actualités
        </a>

        <header class="mt-6">
            <time datetime="{{ $post->created_at->toDateString() }}" class="text-sm font-semibold uppercase tracking-wide text-brand-600">
                {{ $post->created_at->translatedFormat('l j F Y') }}
            </time>
            <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight">{{ $post->headline() }}</h1>
            <div class="mt-5 flex items-center gap-3">
                <x-avatar :user="$post->user" size="h-10 w-10" />
                <div class="text-sm">
                    <div class="font-semibold">{{ $post->user->name }}</div>
                    <div class="text-gray-500">{{ $post->user->bureau_title ?? $post->user->roleLabel() }}</div>
                </div>
            </div>
        </header>

        @if ($post->body)
            <div class="mt-8 whitespace-pre-line break-words text-lg leading-relaxed text-gray-700">{{ $post->body }}</div>
        @endif

        @if ($post->media->isNotEmpty())
            <div class="mt-8 grid gap-4 {{ $post->media->count() > 1 ? 'sm:grid-cols-2' : '' }}">
                @foreach ($post->media as $media)
                    @if ($media->isVideo())
                        <video src="{{ $media->url() }}" controls preload="metadata" playsinline class="w-full rounded-2xl bg-black {{ $post->media->count() > 1 ? 'sm:col-span-2' : '' }}"></video>
                    @else
                        <a href="{{ $media->url() }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-2xl bg-gray-100">
                            <img src="{{ $media->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        <aside class="mt-12 card flex flex-col items-start gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold">Vous êtes étudiant guinéen à Rennes ?</h2>
                <p class="mt-1 text-sm text-gray-600">Rejoignez l'association pour participer à la vie de la communauté.</p>
            </div>
            <a href="{{ auth()->check() ? route('feed') : route('register') }}" class="btn-primary shrink-0">
                {{ auth()->check() ? 'Espace membres' : 'Rejoindre l\'AG35' }}
            </a>
        </aside>
    </article>
</x-site-layout>
