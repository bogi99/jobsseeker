<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.customer-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $customerType = UserType::where('name', 'customer')->first();

        if (! $customerType) {
            abort(503, 'Customer user type is not configured.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'usertype_id' => $customerType->id,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return redirect()
            ->route('jobs.index')
            ->with('status', 'Welcome aboard! Your customer account is ready.');
    }
}
