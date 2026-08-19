<?php

namespace App\Filament\User\Pages;

use App\Models\TaxationService;
use App\Models\UserPlanApiAccess;
use App\Services\Taxation\TaxationCatalog;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class TaxationServices extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 21;

    protected static ?string $navigationLabel = 'Taxation Services';

    protected static ?string $title = 'Taxation service IDs';

    protected static ?string $slug = 'taxation-services';

    protected string $view = 'filament.user.pages.taxation-services';

    public string $search = '';

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
     * @return Collection<int, array{id: int, name: string, category: string, price: float}>
     */
    public function services(): Collection
    {
        $needle = strtolower(trim($this->search));

        return TaxationService::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(function (TaxationService $service) use ($needle) {
                if ($needle === '') {
                    return true;
                }

                return str_contains(strtolower($service->name), $needle)
                    || str_contains((string) $service->id, $needle)
                    || str_contains(strtolower((string) $service->category?->name), $needle);
            })
            ->map(fn (TaxationService $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'category' => (string) $service->category?->name,
                'price' => (float) $service->price,
            ])
            ->values();
    }
}
