<?php

namespace App\Filament\Partner\Resources\KycApplications\Pages;

use App\Actions\Onboarding\ApproveDeveloper;
use App\Actions\Onboarding\RejectDeveloper;
use App\Enums\OnboardingStatus;
use App\Filament\Partner\Resources\KycApplications\KycApplicationResource;
use App\Models\KycDocument;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewKycApplication extends ViewRecord
{
    protected static string $resource = KycApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadDocument')
                ->label('Download documents')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => $this->record->kycDocuments->isNotEmpty())
                ->modalHeading('Documents')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(function (): \Illuminate\Contracts\View\View {
                    return view('filament.kyc.document-list', [
                        'documents' => $this->record->kycDocuments,
                        'record' => $this->record,
                    ]);
                }),
            Action::make('approve')
                ->label('Approve & issue UAT keys')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalDescription('Approves login access and auto-provisions active UAT API credentials.')
                ->visible(fn (): bool => $this->record->onboarding_status === OnboardingStatus::KycSubmitted
                    && $this->belongsToPartner())
                ->action(function (ApproveDeveloper $approve): void {
                    /** @var User $record */
                    $record = $this->record;
                    abort_unless($this->belongsToPartner(), 403);
                    $approve->handle($record, auth()->user());
                    $this->record->refresh();
                    Notification::make()
                        ->title('Developer approved')
                        ->body('UAT credentials were issued automatically.')
                        ->success()
                        ->send();
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => in_array($this->record->onboarding_status, [
                    OnboardingStatus::KycSubmitted,
                    OnboardingStatus::PendingKyc,
                ], true) && $this->belongsToPartner())
                ->form([
                    Textarea::make('reason')
                        ->label('Rejection reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data, RejectDeveloper $reject): void {
                    /** @var User $record */
                    $record = $this->record;
                    abort_unless($this->belongsToPartner(), 403);
                    $reject->handle($record, $data['reason']);
                    $this->record->refresh();
                    Notification::make()
                        ->title('Application rejected')
                        ->warning()
                        ->send();
                }),
        ];
    }

    public function downloadKycDocument(int $documentId): StreamedResponse
    {
        abort_unless($this->belongsToPartner(), 403);

        /** @var KycDocument $document */
        $document = $this->record->kycDocuments()->whereKey($documentId)->firstOrFail();

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return $document->downloadResponse();
    }

    private function belongsToPartner(): bool
    {
        return (int) $this->record->whitelabel_id === (int) auth()->user()?->whitelabel_id
            && auth()->user()?->whitelabel_id !== null;
    }
}
