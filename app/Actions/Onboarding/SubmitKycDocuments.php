<?php

namespace App\Actions\Onboarding;

use App\Enums\OnboardingStatus;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitKycDocuments
{
    /**
     * @param  list<array{type: string, file: UploadedFile}>  $documents
     */
    public function handle(User $user, array $documents, ?string $companyName = null, ?string $phone = null): User
    {
        if (! in_array($user->onboarding_status, [OnboardingStatus::PendingKyc, OnboardingStatus::Rejected], true)) {
            throw ValidationException::withMessages([
                'documents' => 'KYC has already been submitted or approved.',
            ]);
        }

        if ($documents === []) {
            throw ValidationException::withMessages([
                'documents' => 'Upload at least one KYC document.',
            ]);
        }

        return DB::transaction(function () use ($user, $documents, $companyName, $phone): User {
            foreach ($documents as $document) {
                /** @var UploadedFile $file */
                $file = $document['file'];
                $path = $file->store('kyc/'.$user->id, 'local');

                KycDocument::query()->create([
                    'user_id' => $user->id,
                    'document_type' => $document['type'],
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => 'local',
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            $user->forceFill([
                'company_name' => $companyName ?: $user->company_name,
                'phone' => $phone ?: $user->phone,
                'onboarding_status' => OnboardingStatus::KycSubmitted,
                'kyc_submitted_at' => now(),
                'kyc_token' => null,
                'kyc_token_expires_at' => null,
                'rejection_reason' => null,
            ])->save();

            return $user->refresh();
        });
    }
}
