<x-app-layout title="Contenu du site">
    @include('admin.partials.nav')

    @php
        $groups = [
            'Identité' => [
                'association_name' => ['Nom de l\'association', 'text'],
                'slogan' => ['Slogan', 'text'],
            ],
            'Présentation' => [
                'about' => ['Texte de présentation', 'textarea'],
                'mission_1' => ['Mission 1', 'text'],
                'mission_2' => ['Mission 2', 'text'],
                'mission_3' => ['Mission 3', 'text'],
            ],
            'Contact' => [
                'contact_email' => ['Email de contact', 'email'],
                'contact_phone' => ['Téléphone', 'tel'],
                'address' => ['Adresse', 'text'],
            ],
            'Réseaux sociaux' => [
                'facebook_url' => ['Page Facebook', 'url'],
                'instagram_url' => ['Compte Instagram', 'url'],
                'whatsapp_url' => ['Lien d\'invitation WhatsApp', 'url'],
            ],
            'Adhésion' => [
                'membership_fee' => ['Cotisation annuelle (€)', 'text'],
            ],
        ];
    @endphp

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $fields)
            <section class="card p-5">
                <h2 class="font-bold">{{ $group }}</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($fields as $key => [$label, $type])
                        <div class="{{ $type === 'textarea' || in_array($key, ['association_name', 'slogan'], true) ? 'sm:col-span-2' : '' }}">
                            <label for="{{ $key }}" class="label">{{ $label }}</label>
                            @if ($type === 'textarea')
                                <textarea id="{{ $key }}" name="{{ $key }}" rows="6" maxlength="3000" class="input">{{ old($key, $settings[$key]) }}</textarea>
                            @else
                                <input id="{{ $key }}" name="{{ $key }}" type="{{ $type }}" class="input" value="{{ old($key, $settings[$key]) }}"
                                       @if ($type === 'url') placeholder="https://…" @endif>
                            @endif
                            <x-input-error :messages="$errors->get($key)" class="mt-1" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" target="_blank" class="text-sm font-semibold text-brand-600 hover:underline">Voir le site public ↗</a>
            <button class="btn-primary">Enregistrer</button>
        </div>
    </form>
</x-app-layout>
