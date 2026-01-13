<x-filament::page>
    <x-slot name="heading">Customer panel</x-slot>
    <x-slot name="subheading">Manage the jobs you list on JobRat without touching the admin console.</x-slot>

    <div class="grid gap-6 md:grid-cols-3">
        <x-filament::card class="space-y-1">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Active postings</p>
            <p class="text-4xl font-semibold text-slate-900">{{ $activePosts }}</p>
            <p class="text-sm text-slate-500">Currently live for applicants</p>
        </x-filament::card>
        <x-filament::card class="space-y-1">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total postings</p>
            <p class="text-4xl font-semibold text-slate-900">{{ $totalPosts }}</p>
            <p class="text-sm text-slate-500">Published since joining</p>
        </x-filament::card>
        <x-filament::card class="space-y-1">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Signed in as</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $userName ?? 'Customer' }}</p>
            <p class="text-sm text-slate-500">{{ now()->format('F j, Y') }}</p>
        </x-filament::card>
    </div>

    <div class="mt-8 space-y-4">
        <x-filament::card class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Next steps</p>
            <ul class="space-y-3 text-sm text-slate-600">
                <li>Use the navigation on the left to manage job postings, tags, and collaboration details.</li>
                <li>Keep your posts active by toggling the <span class="font-semibold">Active</span> switch—the status
                    updates instantly.</li>
                <li>Need to add more context? Attach full job descriptions and company images from each post.</li>
            </ul>
            <x-filament::button tag="a" href="{{ route('jobs.index') }}" color="primary" size="sm">
                Browse public job board
            </x-filament::button>
        </x-filament::card>

        <x-filament::card class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Ready to post?</p>
            <p class="text-sm text-slate-600">Launch a new job directly from the customer experience and keep the public
                board updated.</p>
            <x-filament::button tag="a"
                href="{{ \App\Filament\Customer\Resources\PostResource::getUrl('create') }}" color="primary">
                Create a job posting
            </x-filament::button>
        </x-filament::card>
    </div>
</x-filament::page>
