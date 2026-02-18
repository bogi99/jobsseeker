<x-layout>
    <div class="mx-auto max-w-2xl px-4 py-16 text-center">
        <h1 class="text-3xl font-semibold">Verify your email address</h1>

        @if (session('status') === 'verification-link-sent')
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-600">
                A new verification link has been sent to your email address.
            </div>
        @endif

        <p class="mt-6 text-lg text-slate-700">Thanks for signing up — please check your email for the verification link.
            If you didn't receive the email, you can request another below.</p>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button class="rounded-xl bg-sky-600 px-6 py-3 text-white hover:bg-sky-700">Resend verification
                email</button>
        </form>

        <div class="mt-6 text-sm text-slate-500">
            <a href="{{ route('jobs.index') }}" class="underline">Return to listings</a>
        </div>
    </div>
</x-layout>
