<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BusinessProfileResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BusinessProfileResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-m-building-office';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Business Profile';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Brand identity')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Business name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', str()->slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('website_url')
                            ->label('Website URL')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('logo_url')
                            ->label('Logo URL')
                            ->url()
                            ->maxLength(255),
                    ]),
                    Forms\Components\Textarea::make('description')
                        ->label('About the business')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),
            Section::make('Contact')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('support_email')
                            ->label('Support email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('support_phone')
                            ->label('Support phone')
                            ->tel()
                            ->maxLength(255),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Business')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('website_url')
                    ->label('Website')
                    ->url(fn (Account $record): ?string => $record->website_url)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('support_email')
                    ->label('Support email')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('support_phone')
                    ->label('Support phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user !== null, fn (Builder $query): Builder => $query->whereKey($user->account_id));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessProfiles::route('/'),
            'edit' => Pages\EditBusinessProfile::route('/{record}/edit'),
        ];
    }
}
