<?php

namespace App\Filament\Resources\ApiEndpoints\RelationManagers;

use App\Enums\SectionKey;
use App\Filament\Support\JsonFormField;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Section layout';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('section_key')
                    ->label('Section')
                    ->options(
                        collect(SectionKey::cases())
                            ->mapWithKeys(fn (SectionKey $key) => [$key->value => $key->label()])
                            ->all()
                    )
                    ->disabled()
                    ->dehydrated(),
                Toggle::make('enabled')
                    ->default(true),
                JsonFormField::make('config', 'Section config (JSON)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('section_key')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('section_key')
                    ->label('Section')
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state instanceof SectionKey) {
                            return $state->label();
                        }

                        return SectionKey::tryFrom((string) $state)?->label() ?? (string) $state;
                    })
                    ->badge()
                    ->searchable(),
                ToggleColumn::make('enabled'),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('config')
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? 'Custom' : 'Default')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Custom' ? 'warning' : 'gray'),
            ])
            ->headerActions([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
