@props(['user' => null, 'size' => 'h-10 w-10', 'text' => 'text-sm', 'online' => false])

<span {{ $attributes->merge(['class' => "relative inline-flex shrink-0 {$size}"]) }}>
    @if ($user?->avatarUrl())
        <img src="{{ $user->avatarUrl() }}" alt="" class="h-full w-full rounded-full object-cover ring-2 ring-white">
    @else
        <span class="flex h-full w-full items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 font-semibold text-white ring-2 ring-white {{ $text }}">
            {{ $user?->initials() ?: '?' }}
        </span>
    @endif
    @if ($online && $user?->isOnline())
        <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 ring-2 ring-white" title="En ligne"></span>
    @endif
</span>
