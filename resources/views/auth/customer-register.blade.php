<x-layout>
    <div class="bg-blue-200">
        <div class="mt-8 flex flex-col-reverse gap-8 lg:flex-row lg:items-stretch px-4 py-8 lg:px-0 lg:py-16">
            <section class="flex-1 rounded-3xl bg-gray-100 p-10 text-slate-900 shadow-2xl">
                <p class="text-sm uppercase tracking-[0.4em] text-slate-600">Community</p>
                <h1 class="mt-6 text-4xl font-semibold leading-tight">Join as a Customer</h1>
                <p class="mt-4 text-lg text-slate-700">
                    Post jobs, manage collaborations, and stay in rhythm with every new talent that appears on
                    JobRat. Your customer dashboard lives right here.<br>
                    <span class="text-sm uppercase tracking-wide text-emerald-600">Secure by default · Always
                        human</span>
                </p>
                <div class="mt-10 grid gap-4 text-sm text-slate-600">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        <p>Access your customer panel at /customer</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-sky-300"></span>
                        <p>One-click onboarding — we'll email a verification link to activate your account</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-300"></span>
                        <p>Build teams, track tags, and invite collaborators</p>
                    </div>
                </div>
            </section>

            <section class="flex-1 rounded-3xl border border-slate-200 bg-gray-100 p-8 shadow-lg text-slate-900">
                <div
                    class="mx-auto mb-6 max-w-lg rounded-2xl border border-sky-200 bg-sky-50/60 px-6 py-5 text-center text-xl font-semibold text-sky-700 shadow-inner">
                    Already registered?
                    <a class="ml-2 inline-block underline decoration-sky-500/80"
                        href="{{ route('filament.customer.auth.login') }}">
                        Log in to your customer panel
                    </a>
                </div>
                <p class="text-sm uppercase tracking-[0.4em] text-slate-600">Register</p>
                <h2 class="mt-2 text-3xl font-semibold text-slate-900">Create Customer account</h2>
                @if (session('status'))
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-600">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-4 space-y-1 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form class="mt-6 space-y-5" method="POST" action="{{ route('customer.register.store') }}">
                    @csrf
                    <label class="block text-sm font-medium text-slate-600">
                        Name ( required )
                        <input
                            class="mt-1 w-full rounded-xl border border-slate-400 px-4 py-3 text-xl text-slate-900 placeholder-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
                            type="text" name="name" value="{{ old('name') }}" required />
                    </label>

                    <label class="block text-sm font-medium text-slate-600">
                        Email ( required )
                        <input
                            class="mt-1 w-full rounded-xl border border-slate-400 px-4 py-3  text-slate-900 placeholder-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 text-xl"
                            type="email" name="email" value="{{ old('email') }}" required />
                    </label>

                    <label class="block text-sm font-medium text-slate-600">
                        Password ( required )
                        <input
                            class="mt-1 w-full rounded-xl border border-slate-400 px-4 py-3  text-slate-900 placeholder-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 text-xl"
                            type="password" name="password" required />
                    </label>

                    <label class="block text-sm font-medium text-slate-600">
                        Confirm password ( required )
                        <input
                            class="mt-1 w-full rounded-xl border border-slate-400 px-4 py-3  text-slate-900 placeholder-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 text-xl"
                            type="password" name="password_confirmation" required />
                    </label>

                    <button
                        class="w-full rounded-2xl bg-linear-to-r from-sky-500 to-emerald-500 px-4 py-3  font-semibold uppercase tracking-[0.2em] text-white transition hover:from-sky-600 hover:to-emerald-600"
                        type="submit">
                        Create customer account
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-layout>
