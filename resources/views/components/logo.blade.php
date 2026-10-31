@props(['dark' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <x-application-logo class="h-9 w-9 shrink-0 drop-shadow-sm" />
    <span class="leading-tight">
        <span class="block text-base font-extrabold tracking-tight {{ $dark ? 'text-white' : 'text-gray-900' }}">AG35</span>
        <span class="block text-[11px] font-medium {{ $dark ? 'text-white/70' : 'text-gray-500' }}">Étudiants guinéens de Rennes</span>
    </span>
</span>
