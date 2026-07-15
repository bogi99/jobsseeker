<x-layout :meta-keywords="$metaKeywords">


    <div class="bg-blue-200 grow flex flex-col">
        <div class="justify-center ">
            <h1 class="text-4xl font-bold text-center my-1">Welcome to
                {{ config('app.site_name') }}</h1>
            <p class="text-center text-lg mb-1">Contact us</p>
        </div>
        <div class="px-4">
            <form action="{{ route('contact.submit') }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
                @csrf

                @if (session('contact_form_sent'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 p-3 text-sm text-green-700">
                        <p>{{ session('status') }}</p>
                        @if (session('contact_form_return_to'))
                            <p class="mt-2">
                                <a href="{{ session('contact_form_return_to') }}" class="font-semibold text-green-800 underline hover:no-underline">
                                    Go back to the previous page
                                </a>
                            </p>
                        @endif
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                
                @if (!session('contact_form_sent'))
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-bold mb-2">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-gray-700 font-bold mb-2">Message</label>
                    <textarea id="message" name="message" rows="5" class="w-full px-3 py-2 border rounded-lg" required>{{ old('message') }}</textarea>
                </div>
                <div class="text-center">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Send Message</button>
                </div>
                @endif
            </form>
        </div>
        <div class="mb-0">&nbsp;</div>
    </div>

</x-layout>