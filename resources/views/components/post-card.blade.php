<div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 mb-4 hover:shadow-lg transition-shadow">
    <!-- Company Header -->
    <div class="flex items-center mb-3">
        @if ($post->company_logo_url)
            <img src="{{ $post->company_logo_url }}" alt="{{ $post->company_name }}"
                class="w-10 h-10 rounded-lg object-cover mr-3">
        @else
            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z">
                    </path>
                </svg>
            </div>
        @endif
        <div>
            <h4 class="text-lg font-semibold text-gray-800">{{ $post->company_name }}</h4>
            <p class="text-xs text-gray-500">Posted by {{ $post->user->name }} •
                {{ $post->created_at->diffForHumans() }}</p>
        </div>
    </div>

    <!-- Post Title -->
    <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $post->title }}</h3>

    <!-- Post Content Preview -->
    <p class="text-gray-700 mb-3 line-clamp-2">
        {{ Str::limit($post->content, 150) }}
    </p>

    <!-- Tags -->
    <div class="flex flex-wrap gap-2 mt-3">
        @foreach ($post->tags as $tag)
            <span
                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-medium cursor-pointer transition-colors">
                {{ $tag->name }}
            </span>
        @endforeach
    </div>

    <!-- Post Status Indicators -->
    <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
        <div class="flex space-x-2">
            @if ($post->is_featured)
                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">Featured</span>
            @endif
            @if ($post->is_paid)
                <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">Premium</span>
            @endif
        </div>
        <a href="{{ route('jobs.show', $post) }}" target="_blank"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
            View Details →
        </a>
    </div>
</div>
