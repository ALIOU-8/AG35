<x-site-layout title="Actualités">
    <section class="bg-gradient-to-br from-brand-700 to-brand-800 text-white">
        <div class="max-w-6xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-wider text-white/70">Actualités</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">La vie de l'association</h1>
            <p class="mt-3 max-w-2xl text-white/80">Événements, rencontres, informations pratiques : les nouvelles partagées par le bureau.</p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
        @if ($posts->isEmpty())
            <p class="card p-10 text-center text-gray-500">Aucune actualité pour le moment.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-news-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </section>
</x-site-layout>
