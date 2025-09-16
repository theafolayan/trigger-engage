<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages\ListEvents;
use App\Filament\Resources\EventResource\Pages\EditEvent;
use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Models\Event;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-bolt';
    protected static string|\UnitEnum|null $navigationGroup = 'Activity';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
            Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
            Tables\Columns\TextColumn::make('contact.email')->label('Contact'),
            Tables\Columns\TextColumn::make('occurred_at')->label('Occurred')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/events'),
            'create' => CreateEvent::route('/events/create'),
            'edit' => EditEvent::route('/events/{record}/edit'),
        ];
    }
}
