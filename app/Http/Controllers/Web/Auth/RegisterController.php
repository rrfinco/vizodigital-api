<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Onboarding\RegisterDeveloper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, RegisterDeveloper $register): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $register->handle($data);

        return redirect()
            ->route('register.thanks')
            ->with('status', 'Check your email for the KYC upload link.');
    }

    public function thanks(): View
    {
        return view('auth.register-thanks');
    }
}
