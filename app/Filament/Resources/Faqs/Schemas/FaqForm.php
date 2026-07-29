<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Filament\Support\PublishStatusField;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ')
                    ->columns(2)
                    ->schema([
                        Select::make('api_version_id')
                            ->label('API version')
                            ->relationship('version', 'name')
                            ->searchable()
                            ->preload(),
                        PublishStatusField::make(),
                        TextInput::make('category')
                            ->helperText('Group label, e.g. Authentication')
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('question')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        MarkdownEditor::make('answer_md')
                            ->label('Answer')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
