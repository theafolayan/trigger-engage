<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushSettingResource\Pages\EditPushSetting;
use App\Models\PushSetting;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Schemas\Schema;



class PushSettingResource extends Resource
{
    protected static ?string $model = PushSetting::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('driver')
                ->options([
                    'one_signal' => 'OneSignal',
                    'expo' => 'Expo',
                ])
                ->required(),
            Forms\Components\TextInput::make('api_key')
                ->password()
                ->afterStateHydrated(fn(Forms\Components\TextInput $component) => $component->state('')),
            Forms\Components\TextInput::make('app_id'),
            Forms\Components\TextInput::make('project_id'),
            Forms\Components\Toggle::make('is_active'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditPushSetting::route('/push-settings'),
        ];
    }
}
