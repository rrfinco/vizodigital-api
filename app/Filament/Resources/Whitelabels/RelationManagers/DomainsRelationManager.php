<?php

namespace App\Filament\Resources\Whitelabels\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'Domains';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('host')
                    ->label('Host')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('partner.example.com')
                    ->helperText('Hostname only — no https://. Point this domain (A/CNAME) to the portal server. Partner login: https://{host}/partner')
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? strtolower(trim($state)) : null),
                Toggle::make('is_primary')
                    ->label('Primary')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('host')
            ->columns([
                TextColumn::make('host')
                    ->searchable()
                    ->fontFamily('mono'),
                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),
                TextColumn::make('verified_at')
                    ->dateTime()
                    ->placeholder('Not verified')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['host'] = strtolower(trim((string) ($data['host'] ?? '')));
                        $data['verified_at'] = $data['verified_at'] ?? now();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
