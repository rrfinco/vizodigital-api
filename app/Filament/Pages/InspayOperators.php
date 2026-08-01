<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithInspayOperators;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\WithPagination;
use UnitEnum;

class InspayOperators extends Page
{
    use InteractsWithInspayOperators;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 26;

    protected static ?string $navigationLabel = 'InsPay Operators';

    protected static ?string $title = 'InsPay Operator Codes';

    protected static ?string $slug = 'inspay-operators';

    protected string $view = 'filament.pages.inspay-operators';
}
