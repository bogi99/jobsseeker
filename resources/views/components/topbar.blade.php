<div class="bg-blue-300 flex items-center">
    @php
        $logos = ['logo.png', 'logo2.png'];
        $randomLogo = $logos[array_rand($logos)];
    @endphp
    <img src="{{ asset('images/' . $randomLogo) }}" alt="JobRat Logo" class="h-16 w-16 scale-x-[-1] m-2 rounded-sm">
    <span class="text-3xl font-bold text-gray-800 ml-2">{{ config('app.site_name') }}</span>
</div>
