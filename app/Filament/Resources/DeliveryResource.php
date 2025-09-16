<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages\ListDeliveries;
use App\Filament\Resources\DeliveryResource\Pages\EditDelivery;
use App\Filament\Resources\DeliveryResource\Pages\CreateDelivery;
use App\Models\Delivery;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-paper-airplane';
    protected static string|\UnitEnum|null $navigationGroup = 'Activity';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
            Tables\Columns\TextColumn::make('contact.email')->label('Contact')->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Status')->sortable(),
            Tables\Columns\TextColumn::make('sent_at')->label('Sent')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveries::route('/deliveries'),
            'create' => CreateDelivery::route('/deliveries/create'),
            'edit' => EditDelivery::route('/deliveries/{record}/edit'),
        ];
    }
}
