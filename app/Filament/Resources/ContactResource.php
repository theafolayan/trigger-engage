<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactResource\Pages\CreateContact;
use App\Filament\Resources\ContactResource\Pages\EditContact;
use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Models\Contact;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-users';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(self::statusOptions())
                            ->default(ContactStatus::Active->value)
                            ->required(),
                        Forms\Components\TextInput::make('first_name')
                            ->label('First name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last name')
                            ->maxLength(255),
                    ]),
                ]),
            Section::make('Lists')
                ->schema([
                    Forms\Components\Select::make('lists')
                        ->label('Lists')
                        ->relationship('lists', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('Assign the contact to one or more lists.'),
                ])
                ->collapsible()
                ->collapsed(),
            Section::make('Attributes')
                ->schema([
                    Forms\Components\KeyValue::make('attributes')
                        ->label('Attributes')
                        ->helperText('Store additional metadata as key-value pairs.')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First name')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last name')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(static function (?ContactStatus $status): string {
                        if ($status === null) {
                            return '';
                        }

                        return Str::title($status->value);
                    }),
                Tables\Columns\TextColumn::make('lists_count')
                    ->label('Lists')
                    ->counts('lists')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(self::statusOptions()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/contacts'),
            'create' => CreateContact::route('/contacts/create'),
            'edit' => EditContact::route('/contacts/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        $options = [];

        foreach (ContactStatus::cases() as $status) {
            $options[$status->value] = Str::title($status->value);
        }

        return $options;
    }
}
