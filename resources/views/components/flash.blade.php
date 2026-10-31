@if ((session('status') && ! in_array(session('status'), ['profile-updated', 'password-updated', 'verification-link-sent'], true)) || session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
         class="mb-4 flex items-start justify-between gap-3 rounded-xl px-4 py-3 text-sm {{ session('error') ? 'bg-red-50 text-red-800 ring-1 ring-red-200' : 'bg-brand-50 text-brand-800 ring-1 ring-brand-200' }}">
        <span>{{ session('error') ?? session('status') }}</span>
        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100"><x-icon name="x" class="h-4 w-4" /></button>
    </div>
@endif
