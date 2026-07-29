<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasCmsResourceAuthorization
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('docs.view_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('docs.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('docs.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('docs.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('docs.delete') ?? false;
    }

    public static function canPublish(): bool
    {
        return auth()->user()?->can('docs.publish') ?? false;
    }
}
