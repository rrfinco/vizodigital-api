<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use Filament\Forms\Components\Select;

class PublishStatusField
{
    public static function make(string $name = 'status'): Select
    {
        return Select::make($name)
            ->options(PublishStatus::class)
            ->default(PublishStatus::Draft->value)
            ->required()
            ->disabled(fn (): bool => ! (auth()->user()?->can('docs.publish') ?? false))
            ->dehydrated()
            ->helperText(fn (): ?string => (auth()->user()?->can('docs.publish') ?? false)
                ? null
                : 'Only publishers can change status. Ask an admin to publish.');
    }
}
