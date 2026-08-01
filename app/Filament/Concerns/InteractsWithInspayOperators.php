<?php

namespace App\Filament\Concerns;

use App\Services\Inspay\InspayOperatorCatalog;
use Filament\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

trait InteractsWithInspayOperators
{
    public string $category = '';

    public string $search = '';

    /** Bumps to remount filter inputs after clear (Livewire select sync). */
    public int $filterVersion = 0;

    public function updatedCategory(): void
    {
        $this->category = trim($this->category);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['category', 'search']);
        $this->category = '';
        $this->search = '';
        $this->filterVersion++;
        $this->resetPage();
    }

    public function clearCategory(): void
    {
        $this->category = '';
        $this->filterVersion++;
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->filterVersion++;
        $this->resetPage();
    }

    public function selectCategory(string $category): void
    {
        $this->category = $category;
        $this->filterVersion++;
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return trim($this->category) !== '' || trim($this->search) !== '';
    }

    public function copyCode(string $code): void
    {
        Notification::make()
            ->title('Copied')
            ->body("Operator code {$code} copied to clipboard.")
            ->success()
            ->send();
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return app(InspayOperatorCatalog::class)->categories();
    }

    /**
     * @return array<string, int>
     */
    public function categoryCounts(): array
    {
        return app(InspayOperatorCatalog::class)
            ->all()
            ->groupBy('category')
            ->map->count()
            ->sortKeys()
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, array{category: string, name: string, code: string}>
     */
    public function operators(): LengthAwarePaginator
    {
        $filtered = app(InspayOperatorCatalog::class)->search(
            category: $this->category !== '' ? $this->category : null,
            query: $this->search !== '' ? $this->search : null,
        );

        $perPage = 24;
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function totalCount(): int
    {
        return app(InspayOperatorCatalog::class)->all()->count();
    }

    public function filteredCount(): int
    {
        return app(InspayOperatorCatalog::class)->search(
            category: $this->category !== '' ? $this->category : null,
            query: $this->search !== '' ? $this->search : null,
        )->count();
    }
}
