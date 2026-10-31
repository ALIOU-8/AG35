<x-guest-layout title="Rejoindre l'association">
    <h1 class="text-xl font-bold">Rejoindre l'AG35</h1>
    <p class="mt-1 mb-6 text-sm text-gray-600">Créez votre compte membre. Un membre du bureau validera votre inscription.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="label">Nom complet</label>
            <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div>
                <label for="phone" class="label">Téléphone <span class="text-gray-400">(facultatif)</span></label>
                <input id="phone" name="phone" type="tel" class="input" value="{{ old('phone') }}" autocomplete="tel">
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>
        </div>

        <div>
            <label for="school" class="label">Établissement</label>
            <input id="school" name="school" type="text" class="input" value="{{ old('school') }}" required
                   placeholder="Université de Rennes, INSA, Rennes School of Business…" list="schools">
            <datalist id="schools">
                <option value="Université de Rennes">
                <option value="Université Rennes 2">
                <option value="INSA Rennes">
                <option value="Rennes School of Business">
                <option value="Sciences Po Rennes">
                <option value="EHESP">
                <option value="ENSAI">
                <option value="Agrocampus / Institut Agro Rennes-Angers">
            </datalist>
            <x-input-error :messages="$errors->get('school')" class="mt-1" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="field_of_study" class="label">Filière</label>
                <input id="field_of_study" name="field_of_study" type="text" class="input" value="{{ old('field_of_study') }}" placeholder="Informatique, Droit…">
                <x-input-error :messages="$errors->get('field_of_study')" class="mt-1" />
            </div>
            <div>
                <label for="study_level" class="label">Niveau</label>
                <select id="study_level" name="study_level" class="input">
                    <option value="">—</option>
                    @foreach (\App\Models\User::STUDY_LEVELS as $level)
                        <option value="{{ $level }}" @selected(old('study_level') === $level)>{{ $level }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('study_level')" class="mt-1" />
            </div>
        </div>

        <div>
            <label for="origin_city" class="label">Ville d'origine en Guinée <span class="text-gray-400">(facultatif)</span></label>
            <input id="origin_city" name="origin_city" type="text" class="input" value="{{ old('origin_city') }}" placeholder="Conakry, Kankan, Labé, N'Zérékoré…">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="label">Mot de passe</label>
                <input id="password" name="password" type="password" class="input" required autocomplete="new-password">
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirmation</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
            </div>
        </div>

        <div class="flex items-center justify-between gap-4 pt-2">
            <a class="text-sm text-gray-600 underline hover:text-gray-900" href="{{ route('login') }}">Déjà membre ? Se connecter</a>
            <button class="btn-primary">Créer mon compte</button>
        </div>
    </form>
</x-guest-layout>
