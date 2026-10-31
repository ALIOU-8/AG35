<x-app-layout title="Publication">
    <div class="mx-auto max-w-2xl space-y-4">
        <a href="{{ route('feed') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900">
            <x-icon name="arrow-left" class="h-4 w-4" /> Fil d'actualité
        </a>

        <x-post-card :post="$post" show-all-comments />
    </div>
</x-app-layout>
