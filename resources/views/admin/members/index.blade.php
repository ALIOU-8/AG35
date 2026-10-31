<x-app-layout title="Gestion des membres">
    @include('admin.partials.nav')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex gap-1 overflow-x-auto text-sm font-medium">
            @foreach (\App\Models\User::STATUSES + ['tous' => 'Tous'] as $key => $label)
                @php($count = $key === 'tous' ? $counts->sum() : ($counts[$key] ?? 0))
                <a href="{{ route('admin.members.index', ['statut' => $key, 'q' => $search ?: null]) }}"
                   class="inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 transition {{ $status === $key ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-900/5' : 'text-gray-600 hover:bg-white/60' }}">
                    {{ $label }}
                    <span class="rounded-full px-1.5 text-[11px] font-bold leading-5 {{ $key === 'pending' && $count > 0 ? 'bg-guinea-yellow text-gray-900' : 'bg-gray-200 text-gray-700' }}">{{ $count }}</span>
                </a>
            @endforeach
        </nav>
        <div class="flex gap-2">
            <a href="{{ route('admin.members.pdf') }}" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> Exporter (PDF)</a>
            <a href="{{ route('admin.members.export') }}" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> Exporter (CSV)</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.members.index') }}" class="mt-4 flex gap-2">
        <input type="hidden" name="statut" value="{{ $status }}">
        <label class="relative flex-1">
            <span class="sr-only">Rechercher</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="search" name="q" value="{{ $search }}" placeholder="Nom, email, établissement…" class="input pl-10">
        </label>
        <button class="btn-primary">Rechercher</button>
    </form>

    <div class="mt-4 card overflow-hidden">
        @if ($members->isEmpty())
            <p class="p-10 text-center text-sm text-gray-500">
                {{ $status === 'pending' && $search === '' ? 'Aucune inscription en attente de validation. 🎉' : 'Aucun membre trouvé.' }}
            </p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($members as $member)
                    <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                        <a href="{{ route('admin.members.edit', $member) }}" class="flex min-w-0 flex-1 items-center gap-3">
                            <x-avatar :user="$member" />
                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span class="font-semibold">{{ $member->name }}</span>
                                    @if ($member->role !== 'membre')
                                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700">{{ $member->bureau_title ?? $member->roleLabel() }}</span>
                                    @endif
                                    @if ($status === 'tous')
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">{{ $member->statusLabel() }}</span>
                                    @endif
                                </span>
                                <span class="block truncate text-sm text-gray-500">{{ $member->email }}</span>
                                <span class="block truncate text-xs text-gray-400">
                                    {{ collect([$member->school, $member->studies()])->filter()->implode(' · ') ?: 'Profil incomplet' }}
                                    · inscrit {{ $member->created_at->diffForHumans() }}
                                </span>
                            </span>
                        </a>
                        <div class="flex shrink-0 items-center gap-2 sm:justify-end">
                            @if ($member->isActive())
                                @php($feeStatus = \App\Models\MembershipFee::paymentStatus((float) $member->paid_current_season, $due))
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ['paid' => 'bg-brand-50 text-brand-700', 'partial' => 'bg-amber-50 text-amber-700', 'unpaid' => 'bg-red-50 text-red-700'][$feeStatus] }}">
                                    Cotisation {{ $season }} {{ ['paid' => '✓', 'partial' => 'partielle', 'unpaid' => 'due'][$feeStatus] }}
                                </span>
                            @endif
                            @unless ($member->isActive())
                                <form method="POST" action="{{ route('admin.members.approve', $member) }}">
                                    @csrf
                                    <button class="btn-primary py-1.5"><x-icon name="check" class="h-4 w-4" /> {{ $member->status === 'suspended' ? 'Réactiver' : 'Valider' }}</button>
                                </form>
                            @endunless
                            <a href="{{ route('admin.members.edit', $member) }}" class="btn-secondary py-1.5">Fiche</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-4">{{ $members->links() }}</div>
</x-app-layout>
