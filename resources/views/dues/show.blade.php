<x-app-layout title="Ma cotisation">
    @php($money = fn (float $value) => number_format($value, 2, ',', ' ').' €')

    <div class="mx-auto max-w-2xl space-y-5">
        <h1 class="text-2xl font-extrabold tracking-tight">Ma cotisation</h1>

        <section class="card overflow-hidden">
            <div class="flag-stripe h-1"></div>
            <div class="p-5 sm:p-6">
                <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Saison {{ $dues['season'] }}</p>

                @if ($dues['due'] <= 0)
                    <p class="mt-2 text-gray-700">Aucune cotisation n'est demandée pour le moment.</p>
                @elseif ($dues['status'] === 'paid')
                    <div class="mt-3 flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600"><x-icon name="check" class="h-6 w-6" /></span>
                        <div>
                            <p class="text-xl font-extrabold text-brand-700">Cotisation réglée</p>
                            <p class="text-sm text-gray-600">Merci ! Vous avez versé {{ $money($dues['paid']) }} pour cette saison.</p>
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-3xl font-extrabold text-gray-900">Reste à régler : {{ $money($dues['remaining']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">
                        Cotisation annuelle : {{ $money($dues['due']) }}
                        @if ($dues['paid'] > 0) · déjà versé : {{ $money($dues['paid']) }} @endif
                    </p>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, (int) round($dues['paid'] / $dues['due'] * 100)) }}%"></div>
                    </div>
                @endif
            </div>
        </section>

        @if ($dues['due'] > 0 && $dues['status'] !== 'paid')
            <section class="card p-5 sm:p-6">
                <h2 class="font-bold">Comment payer</h2>
                @if ($instructions)
                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $instructions }}</p>
                @endif
                <p class="mt-3 text-xs text-gray-500">Dès que le bureau aura enregistré votre paiement, vous recevrez une confirmation dans vos notifications.</p>

                @if ($treasurers->isNotEmpty())
                    <div class="mt-4 space-y-2">
                        @foreach ($treasurers as $treasurer)
                            <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3">
                                <x-avatar :user="$treasurer" size="h-10 w-10" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold">{{ $treasurer->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $treasurer->bureau_title }}</p>
                                </div>
                                <form method="POST" action="{{ route('conversations.direct', $treasurer) }}">
                                    @csrf
                                    <button class="btn-secondary py-1.5"><x-icon name="chat" class="h-4 w-4" /> Écrire</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section class="card p-5 sm:p-6">
            <h2 class="font-bold">Historique de mes paiements</h2>
            <ul class="mt-3 divide-y divide-gray-100">
                @forelse ($history as $fee)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span>
                            <span class="font-semibold">{{ $fee->season }}</span>
                            <span class="block text-xs text-gray-500">{{ $fee->paid_on->translatedFormat('j F Y') }} · {{ $fee->method }}</span>
                        </span>
                        <span class="font-semibold">{{ $money((float) $fee->amount) }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-500">Aucun paiement enregistré pour le moment.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
