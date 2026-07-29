<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KycInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Complete your KYC verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.kyc-invite',
            with: [
                'user' => $this->user,
                'url' => route('onboarding.kyc.show', ['token' => $this->user->kyc_token]),
            ],
        );
    }
}
