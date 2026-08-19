<?php

namespace App\Filament\User\Pages;

use App\Models\TaxationDocument;
use App\Models\TaxationOrder;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Services\Taxation\TaxationApiService;
use App\Services\Taxation\TaxationCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use UnitEnum;

class TaxationOrders extends Page
{
    use WithFileUploads;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 22;

    protected static ?string $navigationLabel = 'Taxation Orders';

    protected static ?string $title = 'Taxation orders';

    protected static ?string $slug = 'taxation-orders';

    protected string $view = 'filament.user.pages.taxation-orders';

    public ?int $uploadOrderId = null;

    public string $documentType = 'pan_card';

    /** @var TemporaryUploadedFile|null */
    public $documentFile = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $access = UserPlanApiAccess::query()
            ->where('user_id', $user->id)
            ->where('service', TaxationCatalog::SERVICE_ACCESS_KEY)
            ->first();

        return $access?->isActive() ?? false;
    }

    /**
     * @return LengthAwarePaginator<int, TaxationOrder>
     */
    public function getOrdersProperty(): LengthAwarePaginator
    {
        return TaxationOrder::query()
            ->with(['client', 'documents'])
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(20);
    }

    /**
     * @return array<string, string>
     */
    public function documentTypes(): array
    {
        return TaxationDocument::TYPES;
    }

    public function startUpload(int $orderId): void
    {
        $this->uploadOrderId = $orderId;
        $this->documentType = 'pan_card';
        $this->documentFile = null;
        $this->resetValidation();
    }

    public function cancelUpload(): void
    {
        $this->uploadOrderId = null;
        $this->documentFile = null;
        $this->resetValidation();
    }

    public function uploadDocument(TaxationApiService $taxation): void
    {
        $this->validate([
            'uploadOrderId' => ['required', 'integer'],
            'documentType' => ['required', 'in:'.implode(',', array_keys(TaxationDocument::TYPES))],
            'documentFile' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        /** @var User $user */
        $user = auth()->user();

        try {
            $taxation->uploadDocuments($user, (int) $this->uploadOrderId, [
                [
                    'type' => $this->documentType,
                    'file' => $this->documentFile,
                ],
            ]);
        } catch (\Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        $this->cancelUpload();
        Notification::make()
            ->title('Document uploaded')
            ->body('Admin will verify and approve the documents.')
            ->success()
            ->send();
    }
}
