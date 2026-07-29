<?php

namespace App\Filament\Resources\MediaAssets\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Media asset')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('path')
                            ->label('File')
                            ->disk('public')
                            ->directory('portal-media')
                            ->storeFileNamesIn('original_name')
                            ->visibility('public')
                            ->required()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (! is_object($state) || ! method_exists($state, 'getMimeType')) {
                                    return;
                                }

                                $set('mime_type', $state->getMimeType());
                                $set('size', $state->getSize());
                                $set('disk', 'public');
                            }),
                        TextInput::make('alt')
                            ->label('Alt text')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('disk')
                            ->default('public')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('mime_type')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('size')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('original_name')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }
}
