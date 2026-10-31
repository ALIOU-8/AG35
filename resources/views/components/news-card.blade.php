@props(['post'])

@php
    $cover = $post->media->first();
@endphp

<a href="{{ route('news.show', $post) }}" {{ $attributes->merge(['class' => 'group card flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-brand-500 to-brand-800">
        @if ($cover && ! $cover->isVideo())
            <img src="{{ $cover->url() }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @elseif ($cover)
            <video src="{{ $cover->url() }}#t=0.5" preload="metadata" muted playsinline class="h-full w-full object-cover"></video>
            <span class="absolute inset-0 flex items-center justify-center">
                <span class="rounded-full bg-black/50 p-3 text-white"><x-icon name="video" class="h-6 w-6" /></span>
            </span>
        @else
            <span class="absolute inset-0 flex items-center justify-center text-4xl font-extrabold text-white/90">AG35</span>
        @endif
        <span class="flag-stripe absolute inset-x-0 bottom-0 h-1"></span>
    </div>
    <div class="flex flex-1 flex-col gap-2 p-5">
        <time datetime="{{ $post->created_at->toDateString() }}" class="text-xs font-medium uppercase tracking-wide text-brand-600">
            {{ $post->created_at->translatedFormat('j F Y') }}
        </time>
        <h3 class="font-bold leading-snug text-gray-900 group-hover:text-brand-700">{{ $post->headline() }}</h3>
        @if (Str::contains(trim((string) $post->body), "\n"))
            <p class="text-sm text-gray-600 line-clamp-3">{{ Str::limit(trim(Str::after(trim($post->body), "\n")), 180) }}</p>
        @endif
        <span class="mt-auto pt-2 text-sm font-semibold text-brand-600">Lire la suite →</span>
    </div>
</a>
