<x-layout>


    <div class="bg-blue-200 grow flex flex-col">
        <div class="justify-center ">
            <h1 class="text-4xl font-bold text-center my-1">Welcome to
                {{ config('app.site_name') }}</h1>
            <p class="text-center text-lg mb-1">Your go-to platform for job postings and tagging.</p>
        </div>
        <div class="px-4">
            @foreach ($activePosts as $post)
                <x-post-card :post="$post" />
            @endforeach
        </div>
        <div class="mb-0">&nbsp;rr</div>
    </div>

</x-layout>
