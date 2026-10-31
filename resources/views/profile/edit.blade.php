<x-app-layout title="Mon profil">
    <div class="mx-auto max-w-3xl space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">Mon profil</h1>
                <p class="text-sm text-gray-500">Ces informations aident les autres membres à vous trouver et à vous contacter.</p>
            </div>
            @if ($user->isActive())
                <a href="{{ route('members.show', $user) }}" class="btn-secondary">Voir mon profil public</a>
            @endif
        </div>

        <div class="card p-5 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-5 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card p-5 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
