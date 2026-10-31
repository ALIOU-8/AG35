<x-app-layout title="Notifications">
    @php
        $badges = [
            'like' => ['heart', 'bg-guinea-red'],
            'comment' => ['comment', 'bg-brand-500'],
            'meeting' => ['video', 'bg-blue-500'],
            'dues' => ['banknotes', 'bg-amber-500'],
        ];
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold tracking-tight">Notifications</h1>
            @if ($items->contains('read', false))
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn-secondary"><x-icon name="check" class="h-4 w-4" /> Tout marquer comme lu</button>
                </form>
            @endif
        </div>

        <div class="card overflow-hidden p-2">
            @forelse ($items as $item)
                @php([$icon, $tone] = $badges[$item['kind']] ?? ['bell', 'bg-gray-500'])
                <a href="{{ $item['url'] }}" class="flex items-center gap-3 rounded-xl p-2.5 transition hover:bg-gray-100 {{ $item['read'] ? '' : 'bg-brand-50/60' }}">
                    <span class="relative shrink-0">
                        @if ($item['actor']['avatar'] ?? null)
                            <img src="{{ $item['actor']['avatar'] }}" alt="" class="h-14 w-14 rounded-full object-cover">
                        @else
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 text-lg font-semibold text-white">{{ $item['actor']['initials'] ?? '?' }}</span>
                        @endif
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full text-white ring-2 ring-white {{ $tone }}">
                            <x-icon :name="$icon" class="h-3.5 w-3.5" />
                        </span>
                    </span>
                    <span class="min-w-0 flex-1 text-sm leading-snug">
                        <span class="text-gray-800">
                            <span class="font-bold text-gray-900">{{ $item['actor']['name'] ?? 'Un membre' }}</span>
                            {{ $item['text'] }}@if ($item['excerpt'])<span class="text-gray-600"> : « {{ $item['excerpt'] }} »</span>@endif
                        </span>
                        <span class="mt-0.5 block text-xs {{ $item['read'] ? 'text-gray-500' : 'font-semibold text-brand-600' }}">{{ $item['time'] }}</span>
                    </span>
                    @unless ($item['read'])
                        <span class="h-3 w-3 shrink-0 rounded-full bg-brand-500" aria-label="Non lue"></span>
                    @endunless
                </a>
            @empty
                <div class="p-10 text-center">
                    <x-icon name="bell" class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 font-semibold">Aucune notification</p>
                    <p class="mt-1 text-sm text-gray-500">Les « j'aime », commentaires et nouvelles réunions apparaîtront ici.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
