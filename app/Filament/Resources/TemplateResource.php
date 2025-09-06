<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateResource\Pages\CreateTemplate;
use App\Filament\Resources\TemplateResource\Pages\EditTemplate;
use App\Filament\Resources\TemplateResource\Pages\ListTemplates;
use App\Models\Template;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('subject')->required(),
            Forms\Components\Textarea::make('html')->rows(10)->required(),
            Forms\Components\Textarea::make('text')->rows(10),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('subject'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemplates::route('/templates'),
            'create' => CreateTemplate::route('/templates/create'),
            'edit' => EditTemplate::route('/templates/{record}/edit'),
        ];
    }
}
