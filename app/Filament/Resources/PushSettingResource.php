<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PushSettingResource\Pages\CreatePushSetting;
use App\Filament\Resources\PushSettingResource\Pages\EditPushSetting;
use App\Filament\Resources\PushSettingResource\Pages\ListPushSettings;
use App\Models\PushSetting;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;



class PushSettingResource extends Resource
{
    protected static ?string $model = PushSetting::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-bell';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('driver')
                ->options([
                    'one_signal' => 'OneSignal',
                    'expo' => 'Expo',
                ])
                ->required()->live(),
            Forms\Components\TextInput::make('api_key')
                ->password()
                ->afterStateHydrated(fn(Forms\Components\TextInput $component) => $component->state('')),
            Forms\Components\TextInput::make('app_id'),
            Forms\Components\TextInput::make('project_id')
                ->live()
                ->visible(fn($get) => $get('driver') !== 'one_signal'),
            Forms\Components\Toggle::make('is_active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('driver'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushSettings::route('/push-settings'),
            'create' => CreatePushSetting::route('/push-settings/create'),
            'edit' => EditPushSetting::route('/push-settings/{record}/edit'),
        ];
    }
}
