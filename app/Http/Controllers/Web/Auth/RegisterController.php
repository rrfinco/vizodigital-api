<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Onboarding\RegisterDeveloper;
use App\Http\Controllers\Controller;
use App\Services\Whitelabel\WhitelabelContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(WhitelabelContext $whitelabel): View
    {
        return view('auth.register', [
            'whitelabel' => $whitelabel->whitelabel(),
        ]);
    }

    public function store(Request $request, RegisterDeveloper $register, WhitelabelContext $whitelabel): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($whitelabel->whitelabel() && ! $whitelabel->isActive()) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Registration is temporarily unavailable on this portal.']);
        }

        if ($whitelabel->id()) {
            $data['whitelabel_id'] = $whitelabel->id();
        }

        $register->handle($data);

        $thanksMessage = $whitelabel->whitelabel()
            ? 'Check your email for the KYC upload link. Your partner will review your documents.'
            : 'Check your email for the KYC upload link.';

        return redirect()
            ->route('register.thanks')
            ->with('status', $thanksMessage);
    }

    public function thanks(): View
    {
        return view('auth.register-thanks');
    }
}
