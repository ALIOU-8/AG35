<x-app-layout title="Membres">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">Membres</h1>
            <p class="text-sm text-gray-500">{{ $members->total() }} {{ $members->total() > 1 ? 'membres' : 'membre' }} {{ $search !== '' || $level ? 'trouvés' : 'dans la communauté' }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('members.index') }}" class="mt-4 card flex flex-col gap-3 p-3 sm:flex-row">
        <label class="relative flex-1">
            <span class="sr-only">Rechercher</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="search" name="q" value="{{ $search }}" placeholder="Nom, établissement, filière, ville d'origine…" class="input pl-10">
        </label>
        <select name="niveau" class="input sm:w-48" onchange="this.form.submit()" aria-label="Niveau d'études">
            <option value="">Tous les niveaux</option>
            @foreach (\App\Models\User::STUDY_LEVELS as $studyLevel)
                <option value="{{ $studyLevel }}" @selected($level === $studyLevel)>{{ $studyLevel }}</option>
            @endforeach
        </select>
        <button class="btn-primary">Rechercher</button>
    </form>

    @if ($members->isEmpty())
        <div class="mt-5 card p-10 text-center text-gray-500">
            Aucun membre ne correspond à votre recherche.
            <a href="{{ route('members.index') }}" class="mt-2 block font-semibold text-brand-600 hover:underline">Réinitialiser</a>
        </div>
    @else
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($members as $member)
                <a href="{{ route('members.show', $member) }}" class="card flex items-center gap-4 p-4 transition hover:shadow-md">
                    <x-avatar :user="$member" size="h-14 w-14" text="text-lg" online />
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="truncate font-semibold">{{ $member->name }}</span>
                        </div>
                        @if ($member->bureau_title)
                            <span class="mt-0.5 inline-block rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700">{{ $member->bureau_title }}</span>
                        @endif
                        @if ($member->studies())
                            <p class="truncate text-sm text-gray-600">{{ $member->studies() }}</p>
                        @endif
                        @if ($member->school)
                            <p class="truncate text-xs text-gray-500">{{ $member->school }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $members->links() }}</div>
    @endif
</x-app-layout>
