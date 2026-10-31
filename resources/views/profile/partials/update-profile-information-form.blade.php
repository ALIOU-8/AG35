<section>
    <header>
        <h2 class="text-lg font-bold text-gray-900">Informations personnelles</h2>
        <p class="mt-1 text-sm text-gray-600">Photo, coordonnées et parcours d'études.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6"
          x-data="{ preview: null, remove: false }">
        @csrf
        @method('patch')

        <div class="flex items-center gap-5">
            <span class="relative h-20 w-20 shrink-0">
                <template x-if="preview"><img :src="preview" alt="" class="h-20 w-20 rounded-full object-cover ring-2 ring-white"></template>
                <x-avatar :user="$user" size="h-20 w-20" text="text-xl" x-show="! preview && ! remove" />
                <span x-cloak x-show="! preview && remove" class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200 text-xl font-semibold text-gray-500">{{ $user->initials() }}</span>
            </span>
            <div class="space-y-2">
                <label class="btn-secondary cursor-pointer">
                    <x-icon name="photo" class="h-4 w-4" /> Changer de photo
                    <input type="file" name="avatar" accept="image/*" class="sr-only"
                           @change="const file = $event.target.files[0]; preview = file ? URL.createObjectURL(file) : null; remove = false">
                </label>
                @if ($user->avatar_path)
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_avatar" value="1" x-model="remove" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Retirer la photo
                    </label>
                @endif
                <x-input-error :messages="$errors->get('avatar')" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Nom complet</label>
                <input id="name" name="name" type="text" class="input" value="{{ old('name', $user->name) }}" required autocomplete="name">
                <x-input-error class="mt-1" :messages="$errors->get('name')" />
            </div>

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email', $user->email) }}" required autocomplete="username">
                <x-input-error class="mt-1" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="mt-2 text-sm text-gray-800">
                        Votre adresse email n'est pas vérifiée.
                        <button form="send-verification" class="text-sm text-gray-600 underline hover:text-gray-900">Renvoyer l'email de vérification</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-green-600">Un nouveau lien de vérification a été envoyé.</p>
                    @endif
                @endif
            </div>

            <div>
                <label for="phone" class="label">Téléphone</label>
                <input id="phone" name="phone" type="tel" class="input" value="{{ old('phone', $user->phone) }}" autocomplete="tel">
                <x-input-error class="mt-1" :messages="$errors->get('phone')" />
            </div>

            <div class="sm:col-span-2">
                <label for="school" class="label">Établissement</label>
                <input id="school" name="school" type="text" class="input" value="{{ old('school', $user->school) }}">
                <x-input-error class="mt-1" :messages="$errors->get('school')" />
            </div>

            <div>
                <label for="field_of_study" class="label">Filière</label>
                <input id="field_of_study" name="field_of_study" type="text" class="input" value="{{ old('field_of_study', $user->field_of_study) }}">
                <x-input-error class="mt-1" :messages="$errors->get('field_of_study')" />
            </div>

            <div>
                <label for="study_level" class="label">Niveau</label>
                <select id="study_level" name="study_level" class="input">
                    <option value="">—</option>
                    @foreach (\App\Models\User::STUDY_LEVELS as $level)
                        <option value="{{ $level }}" @selected(old('study_level', $user->study_level) === $level)>{{ $level }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('study_level')" />
            </div>

            <div>
                <label for="origin_city" class="label">Ville d'origine en Guinée</label>
                <input id="origin_city" name="origin_city" type="text" class="input" value="{{ old('origin_city', $user->origin_city) }}">
                <x-input-error class="mt-1" :messages="$errors->get('origin_city')" />
            </div>

            <div>
                <label for="arrival_year" class="label">Année d'arrivée à Rennes</label>
                <input id="arrival_year" name="arrival_year" type="number" min="1980" max="{{ now()->year + 1 }}" class="input" value="{{ old('arrival_year', $user->arrival_year) }}">
                <x-input-error class="mt-1" :messages="$errors->get('arrival_year')" />
            </div>

            <div class="sm:col-span-2">
                <label for="bio" class="label">Présentation</label>
                <textarea id="bio" name="bio" rows="4" maxlength="1000" class="input" placeholder="Quelques mots sur vous, vos centres d'intérêt, ce que vous pouvez apporter à la communauté…">{{ old('bio', $user->bio) }}</textarea>
                <x-input-error class="mt-1" :messages="$errors->get('bio')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button class="btn-primary">Enregistrer</button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-brand-700">Profil enregistré.</p>
            @endif
        </div>
    </form>
</section>
