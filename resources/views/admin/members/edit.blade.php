<x-app-layout :title="'Fiche de '.$member->name">
    @include('admin.partials.nav')

    <a href="{{ route('admin.members.index', ['statut' => $member->status]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900">
        <x-icon name="arrow-left" class="h-4 w-4" /> Retour à la liste
    </a>

    <div class="mt-4 grid gap-5 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.members.update', $member) }}" class="card space-y-5 p-5 lg:col-span-2">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-4">
                <x-avatar :user="$member" size="h-16 w-16" text="text-lg" />
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold">{{ $member->name }}</h2>
                    <p class="text-sm text-gray-500">Inscrit le {{ $member->created_at->translatedFormat('j F Y') }}@if ($member->approved_at) · validé le {{ $member->approved_at->translatedFormat('j F Y') }}@endif</p>
                    <a href="{{ route('members.show', $member) }}" class="text-sm font-semibold text-brand-600 hover:underline">Voir le profil</a>
                </div>
            </div>

            <fieldset class="grid gap-4 sm:grid-cols-2">
                <legend class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">Identité</legend>
                @foreach ([
                    ['name', 'Nom complet', 'text', true],
                    ['email', 'Email', 'email', true],
                    ['phone', 'Téléphone', 'tel', false],
                    ['origin_city', "Ville d'origine", 'text', false],
                ] as [$field, $label, $type, $required])
                    <div>
                        <label for="{{ $field }}" class="label">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="input" value="{{ old($field, $member->{$field}) }}" @required($required)>
                        <x-input-error :messages="$errors->get($field)" class="mt-1" />
                    </div>
                @endforeach
            </fieldset>

            <fieldset class="grid gap-4 sm:grid-cols-2">
                <legend class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">Études</legend>
                <div class="sm:col-span-2">
                    <label for="school" class="label">Établissement</label>
                    <input id="school" name="school" type="text" class="input" value="{{ old('school', $member->school) }}">
                    <x-input-error :messages="$errors->get('school')" class="mt-1" />
                </div>
                <div>
                    <label for="field_of_study" class="label">Filière</label>
                    <input id="field_of_study" name="field_of_study" type="text" class="input" value="{{ old('field_of_study', $member->field_of_study) }}">
                    <x-input-error :messages="$errors->get('field_of_study')" class="mt-1" />
                </div>
                <div>
                    <label for="study_level" class="label">Niveau</label>
                    <select id="study_level" name="study_level" class="input">
                        <option value="">—</option>
                        @foreach (\App\Models\User::STUDY_LEVELS as $studyLevel)
                            <option value="{{ $studyLevel }}" @selected(old('study_level', $member->study_level) === $studyLevel)>{{ $studyLevel }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('study_level')" class="mt-1" />
                </div>
                <div>
                    <label for="arrival_year" class="label">Arrivée à Rennes</label>
                    <input id="arrival_year" name="arrival_year" type="number" min="1980" max="{{ now()->year + 1 }}" class="input" value="{{ old('arrival_year', $member->arrival_year) }}">
                    <x-input-error :messages="$errors->get('arrival_year')" class="mt-1" />
                </div>
            </fieldset>

            <fieldset class="grid gap-4 sm:grid-cols-2">
                <legend class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">Association</legend>
                <div>
                    <label for="status" class="label">Statut</label>
                    <select id="status" name="status" class="input">
                        @foreach (\App\Models\User::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $member->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>
                <div>
                    <label for="role" class="label">Rôle</label>
                    @can('admin')
                        <select id="role" name="role" class="input">
                            @foreach (\App\Models\User::ROLES as $key => $label)
                                <option value="{{ $key }}" @selected(old('role', $member->role) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="role" type="text" class="input bg-gray-50" value="{{ $member->roleLabel() }}" disabled>
                        <p class="mt-1 text-xs text-gray-500">Seul un administrateur attribue les rôles.</p>
                    @endcan
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>
                <div>
                    <label for="bureau_title" class="label">Fonction au bureau</label>
                    <input id="bureau_title" name="bureau_title" type="text" maxlength="80" class="input" value="{{ old('bureau_title', $member->bureau_title) }}" placeholder="Président(e), Trésorier(ère)…">
                    <x-input-error :messages="$errors->get('bureau_title')" class="mt-1" />
                </div>
                <div>
                    <label for="bureau_order" class="label">Ordre d'affichage</label>
                    <input id="bureau_order" name="bureau_order" type="number" min="1" max="50" class="input" value="{{ old('bureau_order', $member->bureau_order) }}" placeholder="1 = en premier">
                    <x-input-error :messages="$errors->get('bureau_order')" class="mt-1" />
                </div>
                <p class="text-xs text-gray-500 sm:col-span-2">Les membres ayant une fonction au bureau apparaissent sur la page d'accueil du site public.</p>
            </fieldset>

            <div class="flex justify-end border-t border-gray-100 pt-4">
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>

        <div class="space-y-5">
            <section class="card p-5">
                <h2 class="font-bold">Cotisations</h2>
                <ul class="mt-3 divide-y divide-gray-100">
                    @forelse ($fees as $fee)
                        <li class="flex items-start justify-between gap-3 py-2.5 text-sm">
                            <div>
                                <div class="font-semibold">{{ $fee->season }} · {{ number_format((float) $fee->amount, 2, ',', ' ') }} €</div>
                                <div class="text-xs text-gray-500">
                                    {{ $fee->paid_on->translatedFormat('j M Y') }} · {{ $fee->method }}@if ($fee->recorder) · par {{ $fee->recorder->name }}@endif
                                </div>
                                @if ($fee->note)
                                    <div class="text-xs text-gray-600">{{ $fee->note }}</div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.fees.destroy', $fee) }}" onsubmit="return confirm('Supprimer cette cotisation ?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-full p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-700" aria-label="Supprimer"><x-icon name="trash" class="h-4 w-4" /></button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-500">Aucune cotisation enregistrée.</li>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('admin.fees.store', $member) }}" class="mt-4 grid gap-3 border-t border-gray-100 pt-4">
                    @csrf
                    <h3 class="text-sm font-semibold">Enregistrer un paiement</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="season" class="label">Saison</label>
                            <input id="season" name="season" type="text" pattern="\d{4}-\d{4}" class="input" value="{{ old('season', $season) }}" required>
                            <x-input-error :messages="$errors->get('season')" class="mt-1" />
                        </div>
                        <div>
                            <label for="amount" class="label">Montant (€)</label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0" class="input" value="{{ old('amount', $defaultFee) }}" required>
                            <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                        </div>
                        <div>
                            <label for="paid_on" class="label">Payée le</label>
                            <input id="paid_on" name="paid_on" type="date" class="input" value="{{ old('paid_on', now()->toDateString()) }}" required>
                            <x-input-error :messages="$errors->get('paid_on')" class="mt-1" />
                        </div>
                        <div>
                            <label for="method" class="label">Moyen</label>
                            <select id="method" name="method" class="input">
                                @foreach (\App\Models\MembershipFee::METHODS as $method)
                                    <option value="{{ $method }}" @selected(old('method') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('method')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <label for="note" class="label">Note <span class="text-gray-400">(facultatif)</span></label>
                        <input id="note" name="note" type="text" maxlength="255" class="input" value="{{ old('note') }}">
                        <x-input-error :messages="$errors->get('note')" class="mt-1" />
                    </div>
                    <button class="btn-primary">Enregistrer la cotisation</button>
                </form>
            </section>

            @can('admin')
                @unless ($member->is(auth()->user()))
                    <section class="card border border-red-100 p-5">
                        <h2 class="font-bold text-red-700">Supprimer le compte</h2>
                        <p class="mt-1 text-sm text-gray-600">Ses publications, commentaires et cotisations seront supprimés. Ses messages resteront visibles comme « Ancien membre ».</p>
                        <form method="POST" action="{{ route('admin.members.destroy', $member) }}" class="mt-3" onsubmit="return confirm(@js('Supprimer définitivement le compte de '.$member->name.' ?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn-danger w-full"><x-icon name="trash" class="h-4 w-4" /> Supprimer définitivement</button>
                        </form>
                    </section>
                @endunless
            @endcan
        </div>
    </div>
</x-app-layout>
