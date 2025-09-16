<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ContactListResource\Pages\ListContactLists;
use App\Filament\Resources\ContactListResource\Pages\EditContactList;
use App\Filament\Resources\ContactListResource\Pages\CreateContactList;
use App\Models\ContactList;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactListResource extends Resource
{
    protected static ?string $model = ContactList::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('slug'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactLists::route('/lists'),
            'create' => CreateContactList::route('/lists/create'),
            'edit' => EditContactList::route('/lists/{record}/edit'),
        ];
    }
}
