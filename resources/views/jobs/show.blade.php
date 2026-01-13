<x-layout>
    <div class="bg-blue-200 grow flex flex-col">
        <div class="px-4 py-6">
            <!-- Close Tab Link -->
            <div class="mb-4">
                <button onclick="window.close()"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium cursor-pointer">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z">
                        </path>
                    </svg>
                    Close Tab
                </button>
            </div>

            <!-- Job Detail Card -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <!-- Company Header -->
                <div class="flex items-center mb-4">
                    @if ($post->company_logo_url)
                        <img src="{{ $post->company_logo_url }}" alt="{{ $post->company_name }}"
                            class="w-16 h-16 rounded-lg object-cover mr-4">
                    @else
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z">
                                </path>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800">{{ $post->company_name }}</h2>
                        <p class="text-sm text-gray-500">Posted by {{ $post->user->name }} •
                            {{ $post->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <!-- Job Title -->
                <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $post->title }}</h1>

                <!-- Status Badges -->
                <div class="flex space-x-2 mb-6">
                    @if ($post->is_featured)
                        <span
                            class="bg-yellow-100 text-yellow-800 text-sm font-medium px-3 py-1 rounded-full">Featured</span>
                    @endif
                    @if ($post->is_paid)
                        <span
                            class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">Premium</span>
                    @endif
                </div>

                <!-- Job Summary -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Job Summary</h3>
                    <p class="text-gray-700 leading-relaxed">{{ $post->content }}</p>
                </div>

                <!-- Full Job Description -->
                @if ($post->full_content)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Full Description</h3>
                        <div class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $post->full_content }}</div>
                    </div>
                @endif

                <!-- Tags -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Skills & Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($post->tags as $tag)
                            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Application Section -->
                <div class="border-t pt-6">
                    @if ($post->application_link)
                        <a href="{{ $post->application_link }}" target="_blank"
                            class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                            Apply for this Job
                            <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z">
                                </path>
                                <path
                                    d="M5 5a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2v-2a1 1 0 10-2 0v2H5V7h2a1 1 0 000-2H5z">
                                </path>
                            </svg>
                        </a>
                    @else
                        <a href="mailto:{{ $post->user->email }}?subject=Application for {{ $post->title }} at {{ $post->company_name }}"
                            class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                            Email Application
                            <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                        </a>
                        <p class="text-sm text-gray-600 mt-2">
                            Send your application directly to {{ $post->user->name }} at {{ $post->company_name }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layout>
