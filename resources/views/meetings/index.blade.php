<x-app-layout title="Réunions">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">Réunions en visio</h1>
                <p class="text-sm text-gray-500">Réunions de bureau, assemblées générales, séances de révision : tout se passe ici, sans application à installer.</p>
            </div>

            @forelse ($upcoming as $meeting)
                @php($status = $meeting->status())
                <article class="card flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                    <span class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl {{ $status === 'open' ? 'bg-guinea-red/10 text-guinea-red' : 'bg-brand-50 text-brand-700' }}">
                        @if ($meeting->scheduled_at)
                            <span class="text-[11px] font-semibold uppercase leading-none">{{ $meeting->scheduled_at->translatedFormat('M') }}</span>
                            <span class="text-xl font-extrabold leading-tight">{{ $meeting->scheduled_at->format('j') }}</span>
                        @else
                            <x-icon name="video" class="h-7 w-7" />
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold">{{ $meeting->title }}</h2>
                            @if ($status === 'open')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-guinea-red/10 px-2 py-0.5 text-[11px] font-semibold text-guinea-red">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-guinea-red"></span> En cours
                                </span>
                            @else
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700">Planifiée</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500">
                            @if ($meeting->scheduled_at)
                                {{ Str::ucfirst($meeting->scheduled_at->translatedFormat('l j F Y à H:i')) }}
                                @if ($status === 'scheduled') · {{ $meeting->scheduled_at->diffForHumans() }} @endif
                            @else
                                Ouverte {{ $meeting->created_at->diffForHumans() }}
                            @endif
                            @if ($meeting->creator) · par {{ $meeting->creator->name }} @endif
                        </p>
                        @if ($meeting->description)
                            <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $meeting->description }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <a href="{{ route('meetings.show', $meeting) }}" class="btn-primary"><x-icon name="video" class="h-4 w-4" /> Rejoindre</a>
                        @if ($meeting->canBeManagedBy(auth()->user()))
                            <x-dropdown align="right" width="w-48">
                                <x-slot name="trigger">
                                    <button type="button" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Options"><x-icon name="dots" /></button>
                                </x-slot>
                                <x-slot name="content">
                                    <form method="POST" action="{{ route('meetings.end', $meeting) }}">
                                        @csrf
                                        <button class="block w-full px-4 py-2 text-start text-sm text-gray-700 hover:bg-gray-100">Terminer la réunion</button>
                                    </form>
                                    <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('Supprimer cette réunion ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="block w-full px-4 py-2 text-start text-sm text-red-700 hover:bg-red-50">Supprimer</button>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card p-10 text-center">
                    <x-icon name="video" class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 font-semibold">Aucune réunion prévue</p>
                    <p class="mt-1 text-sm text-gray-500">Lancez une visio instantanée ou planifiez la prochaine réunion.</p>
                </div>
            @endforelse

            @if ($past->isNotEmpty())
                <section class="card p-4">
                    <h2 class="font-bold">Réunions passées</h2>
                    <ul class="mt-2 divide-y divide-gray-100">
                        @foreach ($past as $meeting)
                            <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium">{{ $meeting->title }}</span>
                                    <span class="block text-xs text-gray-500">Terminée {{ $meeting->ended_at->diffForHumans() }}@if ($meeting->creator) · {{ $meeting->creator->name }}@endif</span>
                                </span>
                                @if ($meeting->canBeManagedBy(auth()->user()))
                                    <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('Supprimer cette réunion de l\'historique ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-full p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-700" aria-label="Supprimer"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside>
            <form method="POST" action="{{ route('meetings.store') }}" class="card space-y-4 p-5 lg:sticky lg:top-20"
                  x-data="{ startNow: @js((bool) old('start_now')) }">
                @csrf
                <h2 class="font-bold">Nouvelle réunion</h2>
                <div>
                    <label for="title" class="label">Titre</label>
                    <input id="title" name="title" type="text" maxlength="120" required class="input" value="{{ old('title') }}" placeholder="Réunion du bureau, AG, révisions…">
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>
                <div>
                    <label for="description" class="label">Description <span class="text-gray-400">(facultatif)</span></label>
                    <textarea id="description" name="description" rows="3" maxlength="2000" class="input" placeholder="Ordre du jour…">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="start_now" value="1" x-model="startNow" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Démarrer maintenant
                </label>
                <div x-show="! startNow">
                    <label for="scheduled_at" class="label">Date et heure</label>
                    <input id="scheduled_at" name="scheduled_at" type="datetime-local" class="input" value="{{ old('scheduled_at') }}"
                           min="{{ now()->format('Y-m-d\TH:i') }}" :required="! startNow" :disabled="startNow">
                    <x-input-error :messages="$errors->get('scheduled_at')" class="mt-1" />
                </div>
                <button class="btn-primary w-full">
                    <x-icon name="video" class="h-4 w-4" />
                    <span x-text="startNow ? 'Lancer la visio' : 'Planifier la réunion'">Planifier la réunion</span>
                </button>
                <p class="text-xs text-gray-500">Autorisez l'accès à la caméra et au micro quand le navigateur le demande. Fonctionne sur ordinateur, Android et iPhone.</p>
            </form>
        </aside>
    </div>
</x-app-layout>
