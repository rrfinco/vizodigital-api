<?php

namespace App\Filament\Resources\TaxationOrders\Pages;

use App\Filament\Resources\TaxationOrders\TaxationOrderResource;
use App\Models\TaxationDocument;
use App\Models\TaxationOrder;
use App\Services\Taxation\TaxationApiService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewTaxationOrder extends ViewRecord
{
    protected static string $resource = TaxationOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadDocuments')
                ->label('Documents')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => $this->record->documents->isNotEmpty())
                ->modalHeading('Order documents')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(function (): View {
                    return view('filament.admin.pages.taxation-order-documents', [
                        'documents' => $this->record->documents,
                    ]);
                }),
            Action::make('verifyDocuments')
                ->label('Mark documents verified')
                ->color('info')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalDescription('Marks uploaded documents as verified. The order stays in processing until you approve.')
                ->visible(fn (): bool => $this->record->documents_status === TaxationOrder::DOCUMENTS_SUBMITTED)
                ->action(function (TaxationApiService $taxation): void {
                    try {
                        $taxation->markDocumentsVerified($this->record, auth()->user());
                        $this->record->refresh();
                        Notification::make()->title('Documents verified')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('approveDocuments')
                ->label('Approve documents')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalDescription('Verifies documents if needed and marks the order completed.')
                ->visible(fn (): bool => in_array($this->record->documents_status, [
                    TaxationOrder::DOCUMENTS_SUBMITTED,
                    TaxationOrder::DOCUMENTS_VERIFIED,
                ], true))
                ->action(function (TaxationApiService $taxation): void {
                    try {
                        $taxation->approveDocuments($this->record, auth()->user());
                        $this->record->refresh();
                        Notification::make()->title('Documents approved. Order completed.')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('rejectDocuments')
                ->label('Reject documents')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => in_array($this->record->documents_status, [
                    TaxationOrder::DOCUMENTS_SUBMITTED,
                    TaxationOrder::DOCUMENTS_VERIFIED,
                ], true))
                ->form([
                    Textarea::make('reason')
                        ->label('Rejection reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data, TaxationApiService $taxation): void {
                    try {
                        $taxation->rejectDocuments($this->record, auth()->user(), $data['reason']);
                        $this->record->refresh();
                        Notification::make()->title('Documents rejected. User can re-upload.')->warning()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            EditAction::make(),
        ];
    }

    public function downloadTaxationDocument(int $documentId): StreamedResponse
    {
        /** @var TaxationDocument $document */
        $document = $this->record->documents()->whereKey($documentId)->firstOrFail();

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return $document->downloadResponse();
    }
}
