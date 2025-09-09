<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SmtpSettingResource\Pages\CreateSmtpSetting;
use App\Filament\Resources\SmtpSettingResource\Pages\EditSmtpSetting;
use App\Filament\Resources\SmtpSettingResource\Pages\ListSmtpSettings;
use App\Models\SmtpSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SmtpSettingResource extends Resource
{
    protected static ?string $model = SmtpSetting::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('host')->required(),
            Forms\Components\TextInput::make('port')->numeric()->required(),
            Forms\Components\TextInput::make('username')->required(),
            Forms\Components\TextInput::make('password')
                ->password()
                ->afterStateHydrated(fn(Forms\Components\TextInput $component) => $component->state('')),
            Forms\Components\TextInput::make('encryption'),
            Forms\Components\TextInput::make('from_name'),
            Forms\Components\TextInput::make('from_email')->email()->required(),
            Forms\Components\TextInput::make('reply_to')->email(),
            Forms\Components\Toggle::make('is_active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('from_email'),
            Tables\Columns\TextColumn::make('host'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmtpSettings::route('/smtp-settings'),
            'create' => CreateSmtpSetting::route('/smtp-settings/create'),
            'edit' => EditSmtpSetting::route('/smtp-settings/{record}/edit'),
        ];
    }
}
