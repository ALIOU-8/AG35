<x-app-layout title="Cotisations">
    @include('admin.partials.nav')

    @php($money = fn (float $value) => number_format($value, 2, ',', ' ').' €')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('admin.dues.index') }}" class="flex items-end gap-2">
            <input type="hidden" name="statut" value="{{ $filter }}">
            <div>
                <label for="saison" class="label">Saison</label>
                <select id="saison" name="saison" class="input w-40" onchange="this.form.submit()">
                    @foreach ($seasons as $option)
                        <option value="{{ $option }}" @selected($option === $season)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dues.export', ['saison' => $season]) }}" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> Journal des paiements (CSV)</a>
            @if ($due > 0 && $counts['a-recouvrer'] > 0)
                <form method="POST" action="{{ route('admin.dues.remind-all') }}"
                      onsubmit="return confirm(@js('Envoyer un rappel aux membres qui n\'ont pas réglé la saison '.$season.' ? Ceux relancés ces 7 derniers jours seront ignorés.'))">
                    @csrf
                    <input type="hidden" name="season" value="{{ $season }}">
                    <button class="btn-primary"><x-icon name="bell" class="h-4 w-4" /> Relancer les impayés</button>
                </form>
            @endif
        </div>
    </div>

    @if ($due <= 0)
        <p class="mt-4 rounded-xl bg-guinea-yellow/20 px-4 py-3 text-sm text-yellow-900 ring-1 ring-guinea-yellow/40">
            Le montant de la cotisation n'est pas défini : indiquez-le dans
            <a href="{{ route('admin.settings.edit') }}" class="font-semibold underline">Contenu du site → Adhésion</a>.
        </p>
    @endif

    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-4 sm:col-span-2 lg:col-span-1">
            <p class="text-sm text-gray-500">Encaissé sur {{ $season }}</p>
            <p class="mt-1 text-2xl font-extrabold">{{ $money($stats['collected']) }}</p>
            <p class="text-xs text-gray-500">sur {{ $money($stats['expected']) }} attendus ({{ $money($due) }} × {{ $stats['members'] }})</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-brand-500" style="width: {{ $stats['rate'] }}%"></div>
            </div>
            <p class="mt-1 text-xs font-semibold text-brand-700">{{ $stats['rate'] }} % recouvré</p>
        </div>
        @foreach ([
            ['paid', 'À jour', 'bg-brand-50 text-brand-700'],
            ['partial', 'Paiement partiel', 'bg-amber-50 text-amber-700'],
            ['unpaid', 'Impayées', 'bg-red-50 text-red-700'],
        ] as [$key, $label, $tone])
            <div class="card flex items-center gap-4 p-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl text-xl font-extrabold {{ $tone }}">{{ $stats[$key] }}</span>
                <span class="text-sm text-gray-600">{{ $label }}<span class="block text-xs text-gray-400">sur {{ $stats['members'] }} membres actifs</span></span>
            </div>
        @endforeach
    </div>

    @if ($recentPayments->isNotEmpty())
        <div class="mt-3 grid gap-3 lg:grid-cols-3">
            <section class="card p-4">
                <h2 class="font-bold">Encaissé par</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($byRecorder as $row)
                        <li class="flex items-center gap-3">
                            @if ($row->recorder)
                                <x-avatar :user="$row->recorder" size="h-9 w-9" text="text-xs" />
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-500">?</span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ $row->recorder?->name ?? 'Ancien membre' }}</span>
                                <span class="block text-xs text-gray-500">{{ $row->payments }} {{ $row->payments > 1 ? 'paiements' : 'paiement' }}@if ($row->recorder?->bureau_title) · {{ $row->recorder->bureau_title }}@endif</span>
                            </span>
                            <span class="text-sm font-bold">{{ $money((float) $row->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card p-4">
                <h2 class="font-bold">Par moyen de paiement</h2>
                <ul class="mt-3 space-y-2.5">
                    @foreach ($byMethod as $row)
                        <li class="flex items-center justify-between gap-3 text-sm">
                            <span>{{ $row->method }} <span class="text-xs text-gray-500">({{ $row->payments }})</span></span>
                            <span class="font-bold">{{ $money((float) $row->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card p-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Derniers paiements</h2>
                    <a href="{{ route('admin.dues.export', ['saison' => $season]) }}" class="text-xs font-semibold text-brand-600 hover:underline">Tout (CSV)</a>
                </div>
                <ul class="mt-3 divide-y divide-gray-100">
                    @foreach ($recentPayments as $payment)
                        <li class="flex items-start justify-between gap-3 py-2 text-sm">
                            <span class="min-w-0">
                                <span class="block truncate font-semibold">{{ $payment->user->name }}</span>
                                <span class="block truncate text-xs text-gray-500">{{ $payment->paid_on->translatedFormat('j M') }} · {{ $payment->method }} · par {{ $payment->recorder?->name ?? 'ancien membre' }}</span>
                            </span>
                            <span class="shrink-0 font-bold">{{ $money((float) $payment->amount) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex gap-1 overflow-x-auto text-sm font-medium">
            @foreach (\App\Http\Controllers\Admin\DuesController::FILTERS as $key => $label)
                <a href="{{ route('admin.dues.index', ['saison' => $season, 'statut' => $key, 'q' => $search ?: null]) }}"
                   class="inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 transition {{ $filter === $key ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-900/5' : 'text-gray-600 hover:bg-white/60' }}">
                    {{ $label }}
                    <span class="rounded-full px-1.5 text-[11px] font-bold leading-5 {{ $key === 'a-recouvrer' && $counts[$key] > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-700' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('admin.dues.index') }}" class="flex gap-2">
            <input type="hidden" name="saison" value="{{ $season }}">
            <input type="hidden" name="statut" value="{{ $filter }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="Rechercher un membre…" class="input w-56">
        </form>
    </div>

    <div class="mt-3 card overflow-hidden">
        @if ($members->isEmpty())
            <p class="p-10 text-center text-sm text-gray-500">
                {{ $filter === 'a-recouvrer' && $search === '' ? 'Tous les membres actifs sont à jour pour '.$season.'. 🎉' : 'Aucun membre trouvé.' }}
            </p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($members as $member)
                    @php($remaining = max(0, $due - $member->paid_amount))
                    <li class="p-4" x-data="{ paying: false }">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a href="{{ route('admin.members.edit', $member) }}" class="flex min-w-0 flex-1 items-center gap-3">
                                <x-avatar :user="$member" />
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold">{{ $member->name }}</span>
                                    <span class="block truncate text-xs text-gray-500">
                                        {{ $money($member->paid_amount) }} / {{ $money($due) }}
                                        @if ($member->last_paid_on) · dernier paiement le {{ \Illuminate\Support\Carbon::parse($member->last_paid_on)->translatedFormat('j M Y') }} @endif
                                        @if ($lastReminders->has($member->id)) · relancé {{ \Illuminate\Support\Carbon::parse($lastReminders[$member->id])->diffForHumans() }} @endif
                                    </span>
                                </span>
                            </a>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ['paid' => 'bg-brand-50 text-brand-700', 'partial' => 'bg-amber-50 text-amber-700', 'unpaid' => 'bg-red-50 text-red-700'][$member->payment_status] }}">
                                    {{ \App\Models\MembershipFee::PAYMENT_STATUSES[$member->payment_status] }}@if ($member->payment_status === 'partial') · reste {{ $money($remaining) }}@endif
                                </span>
                                <button type="button" @click="paying = ! paying" class="btn-secondary py-1.5"><x-icon name="plus" class="h-4 w-4" /> Paiement</button>
                                @if ($member->payment_status !== 'paid' && $due > 0)
                                    <form method="POST" action="{{ route('admin.dues.remind', $member) }}">
                                        @csrf
                                        <input type="hidden" name="season" value="{{ $season }}">
                                        <button class="btn-secondary py-1.5" title="Envoyer un rappel dans sa cloche et en notification"><x-icon name="bell" class="h-4 w-4" /> Relancer</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <form x-cloak x-show="paying" x-transition method="POST" action="{{ route('admin.fees.store', $member) }}"
                              class="mt-3 grid gap-3 rounded-xl bg-gray-50 p-3 sm:grid-cols-5 sm:items-end">
                            @csrf
                            <input type="hidden" name="season" value="{{ $season }}">
                            <div>
                                <label class="label">Montant (€)</label>
                                <input name="amount" type="number" step="0.01" min="0" required class="input" value="{{ $remaining > 0 ? $remaining : $due }}">
                            </div>
                            <div>
                                <label class="label">Payé le</label>
                                <input name="paid_on" type="date" required class="input" value="{{ now()->toDateString() }}">
                            </div>
                            <div>
                                <label class="label">Moyen</label>
                                <select name="method" class="input">
                                    @foreach (\App\Models\MembershipFee::METHODS as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Note</label>
                                <input name="note" type="text" maxlength="255" class="input" placeholder="Facultatif">
                            </div>
                            <button class="btn-primary">Enregistrer</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
