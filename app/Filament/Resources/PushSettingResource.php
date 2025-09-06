<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PushSettingResource\Pages\EditPushSetting;
use App\Models\PushSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class PushSettingResource extends Resource
{
    protected static ?string $model = PushSetting::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('driver')
                ->options([
                    'one_signal' => 'OneSignal',
                    'expo' => 'Expo',
                ])
                ->required(),
            Forms\Components\TextInput::make('api_key')
                ->password()
                ->afterStateHydrated(fn (Forms\Components\TextInput $component) => $component->state('')),
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

