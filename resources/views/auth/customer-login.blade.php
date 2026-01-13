<x-layout>
    <div class="bg-blue-200">
        <div class="mt-8 flex flex-col-reverse gap-8 lg:flex-row lg:items-stretch px-4 py-8 lg:px-0 lg:py-16">
            <section class="flex-1 rounded-3xl bg-gray-100 p-10 text-slate-900 shadow-2xl">
                <p class="text-sm uppercase tracking-[0.4em] text-slate-600">Community</p>
                <h1 class="mt-6 text-4xl font-semibold leading-tight">Welcome back to JobRat</h1>
                <p class="mt-4 text-lg text-slate-700">
                    Sign in to monitor applicants, collaborate with your team, and keep every tag organized from the
                    customer dashboard.
                    <br>
                    <span class="text-sm uppercase tracking-wide text-emerald-600">Secure by default · Always
                        human</span>
                </p>
                <div class="mt-10 grid gap-4 text-sm text-slate-600">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        <p>Jump straight into /customer to manage posts & collaborators</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-sky-300"></span>
                        <p>Monitor hiring progress with instant alerts</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-300"></span>
                        <p>Tag filtering keeps your shortlist razor sharp</p>
                    </div>
                </div>
            </section>

            <section class="flex-1 rounded-3xl border border-slate-200 bg-gray-100 p-8 shadow-lg text-slate-900">
                <div
                    class="mx-auto mb-6 max-w-lg rounded-2xl border border-sky-200 bg-sky-50/60 px-6 py-5 text-center text-xl font-semibold text-sky-700 shadow-inner">
                    Need an account?
                    <a class="ml-2 inline-block underline decoration-sky-500/80"
                        href="{{ route('customer.register') }}">
                        Create a customer profile
                    </a>
                </div>
                <p class="text-sm uppercase tracking-[0.4em] text-slate-600">Sign In</p>
                <h2 class="mt-2 text-3xl font-semibold text-slate-900">Customer panel</h2>

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

                <form class="mt-6 space-y-5" method="POST" action="{{ route('customer.login.store') }}">
                    @csrf
                    <label class="block text-sm font-medium text-slate-600">
                        Email ( required )
                        <input
                            class="mt-1 w-full rounded-xl border border-slate-400 px-4 py-3 text-xl text-slate-900 placeholder-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
                            type="email" name="email" value="{{ old('email') }}" required autofocus />
                    </label>

                    <label class="block text-sm font-medium text-slate-600">
                        Password ( required )
                        <input
                            class="mt-1 w-full rounded-xl border border-slate-400 px-4 py-3 text-xl text-slate-900 placeholder-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
                            type="password" name="password" required />
                    </label>

                    <label class="flex items-center gap-3 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                            class="accent-sky-500" />
                        Remember me
                    </label>

                    <button
                        class="w-full rounded-2xl bg-gradient-to-r from-sky-500 to-emerald-500 px-4 py-3 text-base font-semibold uppercase tracking-[0.2em] text-white transition hover:from-sky-600 hover:to-emerald-600"
                        type="submit">
                        Log in to customer panel
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-layout>
