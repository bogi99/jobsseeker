<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.customer-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $customerType = UserType::where('name', 'customer')->first();

        if (! $customerType) {
            abort(503, 'Customer user type is not configured.');
        }

        if (! Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'usertype_id' => $customerType->id,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()
            ->route('filament.customer.home')
            ->with('status', 'Welcome back to JobRat.');
    }
}
