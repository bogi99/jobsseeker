<x-layout>
    <div class="bg-blue-200 grow flex flex-col">
        <div class="justify-center">
            <h1 class="text-4xl font-bold text-center my-1">All Job Listings</h1>
            <p class="text-center text-lg mb-1">Browse all available job opportunities on {{ config('app.site_name') }}.
            </p>

            <!-- Jobs Count & Per Page Selector -->
            <div class="flex items-center justify-center gap-4 mb-2">
                <p class="text-sm text-gray-600">
                    Showing {{ $jobs->firstItem() ?? 0 }}-{{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }} jobs
                </p>
                <form method="GET" action="{{ route('jobs.index') }}" class="flex items-center gap-2">
                    <label for="per_page" class="text-sm text-gray-600">Show:</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()"
                        class="text-sm border border-gray-300 rounded px-2 py-1 bg-white">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <span class="text-sm text-gray-600">per page</span>
                </form>
            </div>
        </div>

        <!-- Tag Filter -->
        <div class="px-4 mb-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Filter by Tags:</h3>

                <!-- Selected Tags (if any) -->
                @if (!empty($selectedTags))
                    <div class="mb-3">
                        <p class="text-xs text-gray-500 mb-2">Active filters:</p>
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach ($selectedTags as $tag)
                                <a href="{{ request()->fullUrlWithQuery(['tags' => array_diff($selectedTags, [$tag])]) }}"
                                    class="inline-flex items-center bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium hover:bg-blue-600 transition-colors">
                                    {{ $tag }}
                                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z">
                                        </path>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                        <a href="{{ route('jobs.index', ['per_page' => $perPage]) }}"
                            class="text-sm text-red-600 hover:text-red-800 underline">Clear all filters</a>
                    </div>
                @endif

                <!-- Available Tags -->
                <div class="flex flex-wrap gap-2">
                    @foreach ($allTags as $tag)
                        @php
                            $isSelected = in_array($tag->name, $selectedTags);
                            $newTags = $isSelected
                                ? array_diff($selectedTags, [$tag->name])
                                : array_merge($selectedTags, [$tag->name]);
                        @endphp
                        <a href="{{ request()->fullUrlWithQuery(['tags' => $newTags, 'page' => 1]) }}"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $isSelected
                                ? 'bg-blue-500 text-white hover:bg-blue-600'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300' }}">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Pagination Top (with consistent spacing) -->
        @if ($jobs->hasPages())
            <div class="px-4 mb-4">
                {{ $jobs->links() }}
            </div>
        @else
            <div class="px-4 mb-5">
                <div class="h-8"></div>
            </div>
        @endif

        <div class="px-4">
            @forelse ($jobs as $job)
                <x-post-card :post="$job" />
            @empty
                <div class="text-center py-12">
                    <div class="w-24 h-24 mx-auto mb-4 text-gray-400">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 mb-1">No jobs found</h3>
                    <p class="text-gray-600">There are currently no active job listings.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if ($jobs->hasPages())
            <div class="px-4 mt-4 mb-2">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
</x-layout>
