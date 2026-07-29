<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Actions\Onboarding\SubmitKycDocuments;
use App\Enums\OnboardingStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KycController extends Controller
{
    public function show(string $token): View
    {
        $user = $this->resolveUser($token);

        return view('onboarding.kyc', [
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token, SubmitKycDocuments $submit): RedirectResponse
    {
        $user = $this->resolveUser($token);

        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*.type' => ['required', 'string', 'max:100'],
            'documents.*.file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $documents = [];
        foreach ($validated['documents'] as $row) {
            $documents[] = [
                'type' => $row['type'],
                'file' => $row['file'],
            ];
        }

        $submit->handle(
            $user,
            $documents,
            $validated['company_name'] ?? null,
            $validated['phone'] ?? null,
        );

        return redirect()
            ->route('onboarding.kyc.submitted')
            ->with('status', 'KYC submitted. You can sign in after admin approval.');
    }

    public function submitted(): View
    {
        return view('onboarding.kyc-submitted');
    }

    private function resolveUser(string $token): User
    {
        $user = User::query()
            ->where('kyc_token', $token)
            ->firstOrFail();

        abort_unless($user->hasValidKycToken(), 410, 'This KYC link has expired. Contact support for a new invite.');

        abort_unless(
            in_array($user->onboarding_status, [OnboardingStatus::PendingKyc, OnboardingStatus::Rejected], true),
            410,
            'This KYC link is no longer valid.'
        );

        return $user;
    }
}
