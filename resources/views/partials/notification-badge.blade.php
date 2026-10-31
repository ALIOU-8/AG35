{{-- Pastille d'icône d'une notification rendue par Alpine ; $kind est une expression JavaScript. --}}
<span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full text-white ring-2 ring-white"
      :class="{ 'bg-guinea-red': {{ $kind }} === 'like', 'bg-brand-500': {{ $kind }} === 'comment' || {{ $kind }} === 'message', 'bg-blue-500': {{ $kind }} === 'meeting', 'bg-amber-500': {{ $kind }} === 'dues' }">
    <x-icon name="heart" class="h-3.5 w-3.5" x-show="{{ $kind }} === 'like'" />
    <x-icon name="comment" class="h-3.5 w-3.5" x-show="{{ $kind }} === 'comment'" />
    <x-icon name="video" class="h-3.5 w-3.5" x-show="{{ $kind }} === 'meeting'" />
    <x-icon name="chat" class="h-3.5 w-3.5" x-show="{{ $kind }} === 'message'" />
    <x-icon name="banknotes" class="h-3.5 w-3.5" x-show="{{ $kind }} === 'dues'" />
</span>
