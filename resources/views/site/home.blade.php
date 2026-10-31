<x-site-layout>
    {{-- Accueil --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800 text-white">
        <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-guinea-yellow/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-guinea-red/20 blur-3xl"></div>
        <div class="relative max-w-6xl mx-auto grid gap-10 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-5 lg:items-center lg:px-8">
            <div class="lg:col-span-3">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider ring-1 ring-white/20">
                    <span class="flag-stripe h-2 w-6 rounded-full"></span> Rennes · Ille-et-Vilaine
                </p>
                <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight sm:text-5xl">{{ $site['association_name'] }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-white/85">{{ $site['slogan'] }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('feed') }}" class="btn bg-white text-brand-700 hover:bg-brand-50 focus:ring-white">Aller à l'espace membres</a>
                    @else
                        <a href="{{ route('register') }}" class="btn bg-guinea-yellow text-gray-900 hover:bg-yellow-300 focus:ring-guinea-yellow">Rejoindre l'association</a>
                        <a href="{{ route('login') }}" class="btn bg-white/10 text-white ring-1 ring-white/30 hover:bg-white/20 focus:ring-white">Espace membres</a>
                    @endauth
                </div>
            </div>

            <dl class="grid grid-cols-3 gap-3 lg:col-span-2 lg:grid-cols-1">
                @foreach ([['members', 'membres actifs', 'users'], ['posts', 'publications partagées', 'photo'], ['meetings', 'réunions organisées', 'video']] as [$key, $label, $icon])
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur lg:flex lg:items-center lg:gap-4">
                        <span class="hidden lg:flex h-11 w-11 items-center justify-center rounded-xl bg-white/15"><x-icon :name="$icon" class="h-6 w-6" /></span>
                        <div>
                            <dt class="sr-only">{{ $label }}</dt>
                            <dd class="text-2xl font-extrabold sm:text-3xl">{{ number_format($stats[$key], 0, ',', ' ') }}</dd>
                            <dd class="text-xs text-white/75 sm:text-sm">{{ $label }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- L'association --}}
    <section id="association" class="scroll-mt-20 max-w-6xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-2 lg:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">L'association</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Une famille loin de la maison</h2>
                <p class="mt-4 whitespace-pre-line text-gray-600 leading-relaxed">{{ $site['about'] }}</p>
            </div>
            <div class="grid gap-4">
                @foreach ([['mission_1', 'user-plus', 'bg-guinea-red/10 text-guinea-red'], ['mission_2', 'users', 'bg-guinea-yellow/20 text-yellow-700'], ['mission_3', 'globe', 'bg-brand-100 text-brand-700']] as [$key, $icon, $tone])
                    @if ($site[$key])
                        <div class="card flex items-start gap-4 p-5">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $tone }}"><x-icon :name="$icon" class="h-6 w-6" /></span>
                            <p class="pt-2 text-gray-700">{{ $site[$key] }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- Actualités --}}
    <section class="bg-gray-50 py-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Actualités</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">La vie de l'association</h2>
                </div>
                <a href="{{ route('news.index') }}" class="btn-secondary">Toutes les actualités</a>
            </div>

            @if ($news->isEmpty())
                <p class="mt-8 card p-8 text-center text-gray-500">Les premières actualités arrivent bientôt.</p>
            @else
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($news as $post)
                        <x-news-card :post="$post" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Bureau --}}
    @if ($bureau->isNotEmpty())
        <section id="bureau" class="scroll-mt-20 max-w-6xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Le bureau</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Les membres élus</h2>
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($bureau as $member)
                    <div class="card flex flex-col items-center p-5 text-center">
                        <x-avatar :user="$member" size="h-20 w-20" text="text-xl" />
                        <h3 class="mt-3 font-bold leading-tight">{{ $member->name }}</h3>
                        <p class="mt-1 text-sm font-medium text-brand-600">{{ $member->bureau_title }}</p>
                        @if ($member->studies())
                            <p class="mt-1 text-xs text-gray-500">{{ $member->studies() }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Adhésion --}}
    <section id="adhesion" class="scroll-mt-20 bg-gradient-to-br from-yellow-50 via-white to-brand-50 py-16">
        <div class="max-w-6xl mx-auto grid gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Adhérer</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Rejoignez la communauté</h2>
                <p class="mt-4 text-gray-600">
                    L'adhésion donne accès à l'espace membres : fil d'actualité, messagerie, groupes d'entraide et réunions en visio.
                    @if ($site['membership_fee'])
                        La cotisation annuelle est de <strong class="text-gray-900">{{ $site['membership_fee'] }} €</strong> par année universitaire.
                    @endif
                </p>
            </div>
            <ol class="grid gap-3">
                @foreach (['Créez votre compte en ligne en quelques minutes.', 'Le bureau vérifie et valide votre inscription.', 'Réglez votre cotisation auprès du trésorier.', 'Profitez de l\'espace membres et de toute la communauté.'] as $step)
                    <li class="card flex items-center gap-4 p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 font-bold text-white">{{ $loop->iteration }}</span>
                        <span class="text-gray-700">{{ $step }}</span>
                    </li>
                @endforeach
                @guest
                    <li class="pt-2"><a href="{{ route('register') }}" class="btn-primary w-full sm:w-auto">Créer mon compte</a></li>
                @endguest
            </ol>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="scroll-mt-20 max-w-6xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Contact</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Écrire au bureau</h2>
                <p class="mt-4 text-gray-600">Une question sur l'arrivée à Rennes, un partenariat, un événement ? Le bureau vous répond rapidement.</p>
                <ul class="mt-6 grid gap-3 text-sm text-gray-700">
                    @if ($site['contact_email'])
                        <li class="flex items-center gap-3"><x-icon name="envelope" class="text-brand-600" /><a href="mailto:{{ $site['contact_email'] }}" class="hover:underline">{{ $site['contact_email'] }}</a></li>
                    @endif
                    @if ($site['contact_phone'])
                        <li class="flex items-center gap-3"><x-icon name="phone" class="text-brand-600" />{{ $site['contact_phone'] }}</li>
                    @endif
                    @if ($site['address'])
                        <li class="flex items-center gap-3"><x-icon name="map-pin" class="text-brand-600" />{{ $site['address'] }}</li>
                    @endif
                </ul>
            </div>

            <div class="card p-6 sm:p-8 lg:col-span-3">
                @if (session('contact_status'))
                    <div class="mb-5 flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">
                        <x-icon name="check" class="h-4 w-4" /> {{ session('contact_status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="grid gap-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="label">Nom</label>
                            <input id="contact-name" name="name" type="text" class="input" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <label for="contact-email" class="label">Email</label>
                            <input id="contact-email" name="email" type="email" class="input" value="{{ old('email') }}" required maxlength="180" autocomplete="email">
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <label for="contact-subject" class="label">Sujet</label>
                        <input id="contact-subject" name="subject" type="text" class="input" value="{{ old('subject') }}" required maxlength="150">
                        <x-input-error :messages="$errors->get('subject')" class="mt-1" />
                    </div>
                    <div>
                        <label for="contact-body" class="label">Message</label>
                        <textarea id="contact-body" name="body" rows="5" class="input" required maxlength="5000">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-1" />
                    </div>
                    {{-- Champ piège : invisible pour les humains. --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="contact-website">Site web</label>
                        <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>
                    <div>
                        <button class="btn-primary"><x-icon name="send" class="h-4 w-4" /> Envoyer le message</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-site-layout>
