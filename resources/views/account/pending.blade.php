<x-guest-layout title="Inscription en attente">
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-guinea-yellow/20 text-yellow-700">
            <x-icon name="lock" class="h-7 w-7" />
        </div>
        @if (auth()->user()->status === 'suspended')
            <h1 class="text-xl font-bold">Compte suspendu</h1>
            <p class="mt-2 text-sm text-gray-600">Votre accès à l'espace membres est suspendu. Contactez le bureau de l'association pour plus d'informations.</p>
        @else
            <h1 class="text-xl font-bold">Bienvenue {{ auth()->user()->name }} !</h1>
            <p class="mt-2 text-sm text-gray-600">
                Votre inscription a bien été reçue. Un membre du bureau va vérifier et valider votre compte.
                Vous aurez ensuite accès au fil d'actualité, à la messagerie et aux réunions.
            </p>
        @endif

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('profile.edit') }}" class="btn-secondary">Compléter mon profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-secondary">Se déconnecter</button>
            </form>
        </div>
    </div>
</x-guest-layout>
