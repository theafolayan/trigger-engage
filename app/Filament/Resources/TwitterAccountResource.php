<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TwitterAccountResource\Pages\ListTwitterAccounts;
use App\Models\TwitterAccount;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TwitterAccountResource extends Resource
{
    protected static ?string $model = TwitterAccount::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integrations';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-hashtag';
    protected static ?string $navigationLabel = 'Twitter Accounts';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('connected_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Handle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Display Name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'connected',
                        'danger' => 'disconnected',
                    ])
                    ->formatStateUsing(static fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('connected_at')
                    ->label('Connected At')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('disconnected_at')
                    ->label('Disconnected At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTwitterAccounts::route('/twitter-accounts'),
        ];
    }
}
