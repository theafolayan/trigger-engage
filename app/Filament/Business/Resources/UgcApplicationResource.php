<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources;

use App\Enums\UgcApplicationStatus;
use App\Filament\Business\Resources\UgcApplicationResource\Pages;
use App\Models\UgcApplication;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class UgcApplicationResource extends Resource
{
    protected static ?string $model = UgcApplication::class;

    protected static ?string $navigationIcon = 'heroicon-m-document-text';

    protected static ?string $navigationGroup = 'UGC';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Application details')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('ugc_task_id')
                            ->label('Task')
                            ->relationship('task', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(self::statusOptions())
                            ->enum(UgcApplicationStatus::class)
                            ->required(),
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submitted at')
                            ->seconds(false),
                    ]),
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('creator_name')
                            ->label('Creator name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('creator_email')
                            ->label('Creator email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ]),
                    Forms\Components\Textarea::make('pitch')
                        ->label('Pitch')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator_name')
                    ->label('Creator')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator_email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => UgcApplicationStatus::Pending->value,
                        'success' => UgcApplicationStatus::Approved->value,
                        'danger' => UgcApplicationStatus::Rejected->value,
                    ])
                    ->formatStateUsing(function (UgcApplicationStatus|string|null $state): string {
                        if ($state instanceof UgcApplicationStatus) {
                            return $state->label();
                        }

                        if (is_string($state) && $state !== '') {
                            return UgcApplicationStatus::from($state)->label();
                        }

                        return UgcApplicationStatus::Pending->label();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUgcApplications::route('/'),
            'create' => Pages\CreateUgcApplication::route('/create'),
            'edit' => Pages\EditUgcApplication::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function statusOptions(): array
    {
        return Arr::mapWithKeys(UgcApplicationStatus::cases(), fn (UgcApplicationStatus $status): array => [
            $status->value => $status->label(),
        ]);
    }
}
