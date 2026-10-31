<x-app-layout :title="$member->name">
    <div class="grid gap-5 lg:grid-cols-3">
        <aside class="space-y-4">
            <section class="card overflow-hidden">
                <div class="h-20 bg-gradient-to-br from-brand-500 to-brand-800"></div>
                <div class="-mt-12 flex flex-col items-center px-5 pb-5 text-center">
                    <x-avatar :user="$member" size="h-24 w-24" text="text-2xl" online class="ring-4 ring-white rounded-full" />
                    <h1 class="mt-3 text-xl font-extrabold">{{ $member->name }}</h1>
                    @if ($member->bureau_title)
                        <p class="mt-1 rounded-full bg-brand-50 px-3 py-0.5 text-sm font-semibold text-brand-700">{{ $member->bureau_title }}</p>
                    @elseif ($member->role !== 'membre')
                        <p class="mt-1 text-sm text-gray-500">{{ $member->roleLabel() }}</p>
                    @endif
                    @unless ($member->isActive())
                        <p class="mt-2 rounded-full bg-guinea-yellow/20 px-3 py-0.5 text-xs font-semibold text-yellow-800">Compte {{ Str::lower($member->statusLabel()) }}</p>
                    @endunless
                    <p class="mt-2 text-xs text-gray-500">
                        @if ($member->isOnline())
                            <span class="text-green-600">En ligne</span>
                        @elseif ($member->last_seen_at)
                            Vu {{ $member->last_seen_at->diffForHumans() }}
                        @endif
                    </p>

                    <div class="mt-4 flex w-full flex-col gap-2">
                        @if ($member->is(auth()->user()))
                            <a href="{{ route('profile.edit') }}" class="btn-secondary w-full">Modifier mon profil</a>
                        @elseif ($member->isActive())
                            <form method="POST" action="{{ route('conversations.direct', $member) }}">
                                @csrf
                                <button class="btn-primary w-full"><x-icon name="chat" class="h-4 w-4" /> Envoyer un message</button>
                            </form>
                        @endif
                        @can('manage-members')
                            <a href="{{ route('admin.members.edit', $member) }}" class="btn-secondary w-full"><x-icon name="cog" class="h-4 w-4" /> Gérer la fiche</a>
                        @endcan
                    </div>
                </div>
            </section>

            <section class="card p-5">
                <h2 class="font-bold">À propos</h2>
                @if ($member->bio)
                    <p class="mt-2 whitespace-pre-line break-words text-sm text-gray-700">{{ $member->bio }}</p>
                @endif
                <dl class="mt-3 space-y-2.5 text-sm">
                    @foreach ([
                        ['academic', 'Études', $member->studies()],
                        ['home', 'Établissement', $member->school],
                        ['map-pin', 'Originaire de', $member->origin_city],
                        ['calendar', 'À Rennes depuis', $member->arrival_year],
                    ] as [$icon, $label, $value])
                        @if ($value)
                            <div class="flex items-start gap-3">
                                <x-icon :name="$icon" class="mt-0.5 h-4 w-4 text-gray-400" />
                                <div>
                                    <dt class="text-xs text-gray-500">{{ $label }}</dt>
                                    <dd class="text-gray-800">{{ $value }}</dd>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </dl>
                @if (! $member->bio && ! $member->studies() && ! $member->school && ! $member->origin_city && ! $member->arrival_year)
                    <p class="mt-2 text-sm text-gray-500">Ce membre n'a pas encore complété son profil.</p>
                @endif
            </section>
        </aside>

        <div class="space-y-4 lg:col-span-2">
            <h2 class="text-lg font-bold">Publications</h2>
            @forelse ($posts as $post)
                <x-post-card :post="$post" />
            @empty
                <div class="card p-8 text-center text-sm text-gray-500">Aucune publication pour le moment.</div>
            @endforelse

            {{ $posts->links() }}
        </div>
    </div>
</x-app-layout>
