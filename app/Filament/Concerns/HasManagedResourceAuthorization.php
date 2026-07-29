<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasManagedResourceAuthorization
{
    abstract protected static function managePermission(): string;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('docs.view_admin') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(static::managePermission()) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can(static::managePermission()) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can(static::managePermission()) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can(static::managePermission()) ?? false;
    }

    public static function canPublish(): bool
    {
        return auth()->user()?->can('docs.publish') ?? false;
    }
}
