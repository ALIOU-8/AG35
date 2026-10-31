<x-app-layout title="Messages du site">
    @include('admin.partials.nav')

    <p class="mb-4 text-sm text-gray-500">Messages envoyés depuis le formulaire de contact du site public. Répondez directement par email.</p>

    @forelse ($messages as $message)
        <article class="card mb-3 p-5">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold">{{ $message->subject }}</h2>
                        @unless ($message->read_at)
                            <span class="rounded-full bg-guinea-yellow px-2 py-0.5 text-[11px] font-bold text-gray-900">Nouveau</span>
                        @endunless
                    </div>
                    <p class="text-sm text-gray-500">
                        {{ $message->name }} · <a href="mailto:{{ $message->email }}" class="hover:underline">{{ $message->email }}</a>
                        · {{ $message->created_at->translatedFormat('j F Y à H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="btn-secondary py-1.5"><x-icon name="envelope" class="h-4 w-4" /> Répondre</a>
                    <form method="POST" action="{{ route('admin.contacts.destroy', $message) }}" onsubmit="return confirm('Supprimer ce message ?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-full p-2 text-gray-400 hover:bg-red-50 hover:text-red-700" aria-label="Supprimer"><x-icon name="trash" class="h-4 w-4" /></button>
                    </form>
                </div>
            </header>
            <p class="mt-3 whitespace-pre-line break-words text-sm text-gray-800">{{ $message->body }}</p>
        </article>
    @empty
        <div class="card p-10 text-center text-sm text-gray-500">Aucun message reçu pour le moment.</div>
    @endforelse

    <div class="mt-4">{{ $messages->links() }}</div>
</x-app-layout>
