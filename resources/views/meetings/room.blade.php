<x-app-layout :title="$meeting->title" :full-width="true">
    @if ($config['driver'] === 'jitsi')
        <div x-data="jitsiRoom(@js($config + ['title' => $meeting->title]))" class="h-[calc(100dvh-8.75rem)] bg-gray-950 md:h-[calc(100dvh-3.75rem)]">
            <div x-ref="frame" class="h-full w-full"></div>
            <p x-cloak x-show="error" x-text="error" class="p-6 text-center text-sm text-white/80"></p>
        </div>
    @else
        <div x-data="meetingRoom(@js($config))" class="flex h-[calc(100dvh-8.75rem)] flex-col bg-gray-950 text-white md:h-[calc(100dvh-3.75rem)]">
            <header class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <h1 class="truncate font-semibold">{{ $meeting->title }}</h1>
                    <p class="text-xs text-white/60">
                        <span x-show="phase === 'joined'" x-text="tiles.length + (tiles.length > 1 ? ' participants' : ' participant')"></span>
                        <span x-show="phase === 'lobby'">Vérifiez votre caméra et votre micro avant d'entrer</span>
                    </p>
                </div>
                <a href="{{ $config['leaveUrl'] }}" x-show="phase === 'lobby'" class="rounded-xl px-3 py-1.5 text-sm text-white/70 hover:bg-white/10 hover:text-white">Retour</a>
            </header>

            {{-- Salle d'attente : aperçu local avant d'entrer --}}
            <div x-show="phase === 'lobby'" class="flex flex-1 flex-col items-center justify-center gap-5 overflow-y-auto px-4 pb-6">
                <div class="relative aspect-video w-full max-w-xl overflow-hidden rounded-2xl bg-gray-800">
                    <video x-ref="preview" autoplay playsinline muted class="h-full w-full scale-x-[-1] object-cover" x-show="camOn && hasCamera"></video>
                    <div x-show="! camOn || ! hasCamera" class="absolute inset-0 flex items-center justify-center">
                        <span class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 text-3xl font-semibold">{{ auth()->user()->initials() }}</span>
                    </div>
                    <div class="absolute inset-x-0 bottom-3 flex justify-center gap-3">
                        <button type="button" @click="toggleMic" :disabled="! hasMic" class="rounded-full p-3 transition" :class="micOn ? 'bg-white/20 hover:bg-white/30' : 'bg-guinea-red hover:bg-red-700'" :aria-label="micOn ? 'Couper le micro' : 'Activer le micro'">
                            <x-icon name="mic" class="h-6 w-6" x-show="micOn" /><x-icon name="mic-off" class="h-6 w-6" x-show="! micOn" x-cloak />
                        </button>
                        <button type="button" @click="toggleCam" :disabled="! hasCamera" class="rounded-full p-3 transition" :class="camOn ? 'bg-white/20 hover:bg-white/30' : 'bg-guinea-red hover:bg-red-700'" :aria-label="camOn ? 'Couper la caméra' : 'Activer la caméra'">
                            <x-icon name="video" class="h-6 w-6" x-show="camOn" /><x-icon name="video-off" class="h-6 w-6" x-show="! camOn" x-cloak />
                        </button>
                    </div>
                </div>
                <p x-show="mediaError" x-text="mediaError" class="max-w-xl text-center text-sm text-guinea-yellow"></p>
                <p x-show="error" x-text="error" class="max-w-xl text-center text-sm text-red-300"></p>
                <button type="button" @click="join" :disabled="joining || ! realtime" class="btn-primary px-8 py-3 text-base">
                    <span x-text="joining ? 'Connexion…' : 'Entrer dans la réunion'"></span>
                </button>
            </div>

            {{-- Réunion --}}
            <div x-cloak x-show="phase === 'joined'" class="flex-1 overflow-y-auto px-3 pb-3">
                <div class="mx-auto grid h-full content-center gap-3"
                     :class="tiles.length === 1 ? 'max-w-4xl grid-cols-1' : (tiles.length <= 4 ? 'max-w-6xl grid-cols-1 sm:grid-cols-2' : 'grid-cols-2 lg:grid-cols-3')">
                    <template x-for="tile in tiles" :key="tile.uuid">
                        <div class="relative aspect-video overflow-hidden rounded-2xl bg-gray-800" :data-tile="tile.uuid">
                            <video autoplay playsinline :muted="tile.self" x-init="attach($el, tile.uuid)" x-show="tile.cam"
                                   class="h-full w-full bg-black" :class="[tile.self && ! tile.screen ? 'scale-x-[-1]' : '', tile.screen ? 'object-contain' : 'object-cover']"></video>
                            <div x-show="! tile.cam" class="absolute inset-0 flex items-center justify-center">
                                <template x-if="tile.avatar"><img :src="tile.avatar" alt="" class="h-20 w-20 rounded-full object-cover sm:h-24 sm:w-24"></template>
                                <template x-if="! tile.avatar">
                                    <span class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 text-2xl font-semibold sm:h-24 sm:w-24" x-text="tile.initials"></span>
                                </template>
                            </div>
                            <div class="absolute bottom-2 left-2 flex max-w-[85%] items-center gap-1.5 rounded-lg bg-black/55 px-2 py-1 text-xs font-medium">
                                <x-icon name="mic-off" class="h-3.5 w-3.5 text-red-400" x-show="! tile.mic" />
                                <span class="truncate" x-text="tile.self ? tile.name + ' (vous)' : tile.name"></span>
                            </div>
                            <span x-show="tile.hand" class="absolute right-2 top-2 rounded-full bg-guinea-yellow px-2 py-1 text-sm text-gray-900" title="Main levée">✋</span>
                            <span x-show="tile.state === 'connecting'" class="absolute left-2 top-2 rounded-lg bg-black/55 px-2 py-1 text-xs text-white/80">Connexion…</span>
                            <span x-show="tile.state === 'failed'" class="absolute left-2 top-2 rounded-lg bg-red-600/80 px-2 py-1 text-xs">Connexion impossible</span>
                        </div>
                    </template>
                </div>
            </div>

            <div x-cloak x-show="phase === 'joined' && needsAudioUnlock" class="px-3 pb-2 text-center">
                <button type="button" @click="unlockAudio" class="rounded-xl bg-guinea-yellow px-4 py-2 text-sm font-semibold text-gray-900">Activer le son</button>
            </div>

            <footer x-cloak x-show="phase === 'joined'" class="flex items-center justify-center gap-2 border-t border-white/10 px-3 py-3 sm:gap-3">
                <button type="button" @click="toggleMic" :disabled="! hasMic" class="rounded-full p-3 transition" :class="micOn ? 'bg-white/15 hover:bg-white/25' : 'bg-guinea-red hover:bg-red-700'" :title="micOn ? 'Couper le micro' : 'Activer le micro'">
                    <x-icon name="mic" class="h-6 w-6" x-show="micOn" /><x-icon name="mic-off" class="h-6 w-6" x-show="! micOn" />
                </button>
                <button type="button" @click="toggleCam" :disabled="! hasCamera" class="rounded-full p-3 transition" :class="camOn ? 'bg-white/15 hover:bg-white/25' : 'bg-guinea-red hover:bg-red-700'" :title="camOn ? 'Couper la caméra' : 'Activer la caméra'">
                    <x-icon name="video" class="h-6 w-6" x-show="camOn" /><x-icon name="video-off" class="h-6 w-6" x-show="! camOn" />
                </button>
                <button type="button" @click="toggleScreen" x-show="canShareScreen" class="hidden rounded-full p-3 transition sm:inline-flex" :class="sharing ? 'bg-brand-500 hover:bg-brand-600' : 'bg-white/15 hover:bg-white/25'" :title="sharing ? 'Arrêter le partage' : 'Partager mon écran'">
                    <x-icon name="screen" class="h-6 w-6" />
                </button>
                <button type="button" @click="toggleHand" class="rounded-full p-3 transition" :class="handUp ? 'bg-guinea-yellow text-gray-900' : 'bg-white/15 hover:bg-white/25'" :title="handUp ? 'Baisser la main' : 'Lever la main'">
                    <x-icon name="hand" class="h-6 w-6" />
                </button>
                <button type="button" @click="leave" class="rounded-full bg-guinea-red px-5 py-3 font-semibold transition hover:bg-red-700" title="Quitter">
                    <x-icon name="phone" class="h-6 w-6 rotate-[135deg]" />
                </button>
                <button type="button" x-show="canEnd" @click="endForAll" class="rounded-xl px-3 py-2 text-sm font-semibold text-red-300 hover:bg-white/10">Terminer pour tous</button>
            </footer>
        </div>
    @endif
</x-app-layout>
