<?php

namespace App\Enums;

enum OnboardingStatus: string
{
    case PendingKyc = 'pending_kyc';
    case KycSubmitted = 'kyc_submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingKyc => 'Pending KYC',
            self::KycSubmitted => 'KYC submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingKyc => 'gray',
            self::KycSubmitted => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function canLogin(): bool
    {
        return $this === self::Approved;
    }
}
