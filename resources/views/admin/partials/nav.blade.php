<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-extrabold tracking-tight">Gestion de l'association</h1>
    <nav class="flex gap-1 overflow-x-auto rounded-xl bg-white p-1 text-sm font-medium shadow-sm ring-1 ring-gray-900/5">
        @foreach ([
            ['admin.members.index', 'admin.members.*', 'users', 'Membres'],
            ['admin.dues.index', 'admin.dues.*', 'banknotes', 'Cotisations'],
            ['admin.contacts.index', 'admin.contacts.*', 'envelope', 'Messages du site'],
            ['admin.settings.edit', 'admin.settings.*', 'globe', 'Contenu du site'],
        ] as [$route, $pattern, $icon, $label])
            <a href="{{ route($route) }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-1.5 transition {{ request()->routeIs($pattern) ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
