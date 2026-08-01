<?php

namespace App\Filament\User\Pages;

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

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Bill Payment Opcode';

    protected static ?string $title = 'Bill Payment Opcode';

    protected static ?string $slug = 'inspay-operators';

    protected string $view = 'filament.pages.inspay-operators';
}
