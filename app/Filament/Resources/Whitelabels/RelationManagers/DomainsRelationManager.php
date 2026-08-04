<?php

namespace App\Filament\Resources\Whitelabels\RelationManagers;

use App\Enums\WhitelabelDomainRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
                    ->placeholder('portal.partner.com')
                    ->helperText('Hostname only — no https://. Point DNS (A/CNAME) at the matching portal or API stack.')
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? strtolower(trim($state)) : null),
                Select::make('role')
                    ->label('Role')
                    ->options(WhitelabelDomainRole::class)
                    ->required()
                    ->default(WhitelabelDomainRole::Portal->value)
                    ->live()
                    ->helperText(fn (?string $state): string => WhitelabelDomainRole::tryFrom((string) $state)?->helperText()
                        ?? 'Choose whether this host is the branded portal or an API environment.'),
                Toggle::make('is_primary')
                    ->label('Primary for role')
                    ->helperText('When several hosts share a role, the primary one is used for docs / credentials base URLs.')
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
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (WhitelabelDomainRole|string|null $state): string => $state instanceof WhitelabelDomainRole
                        ? $state->label()
                        : (WhitelabelDomainRole::tryFrom((string) $state)?->label() ?? (string) $state)),
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
                        $data['role'] = $data['role'] ?? WhitelabelDomainRole::Portal->value;
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
