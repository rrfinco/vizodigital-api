<?php

namespace App\Filament\Support;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;

class JsonFormField
{
    public static function make(string $name, string $label = 'JSON'): CodeEditor
    {
        return CodeEditor::make($name)
            ->label($label)
            ->language(Language::Json)
            ->columnSpanFull()
            ->formatStateUsing(function (mixed $state): ?string {
                if ($state === null || $state === '') {
                    return null;
                }

                if (is_string($state)) {
                    return $state;
                }

                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            })
            ->dehydrateStateUsing(function (?string $state): ?array {
                if (! filled($state)) {
                    return null;
                }

                $decoded = json_decode($state, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return null;
                }

                return is_array($decoded) ? $decoded : null;
            })
            ->rules(['nullable', 'json']);
    }
}
