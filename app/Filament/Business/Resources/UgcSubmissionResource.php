<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources;

use App\Enums\UgcSubmissionStatus;
use App\Filament\Business\Resources\UgcSubmissionResource\Pages;
use App\Models\UgcApplication;
use App\Models\UgcSubmission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class UgcSubmissionResource extends Resource
{
    protected static ?string $model = UgcSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-m-paper-airplane';

    protected static ?string $navigationGroup = 'UGC';

    protected static ?string $navigationLabel = 'Submissions';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Submission details')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('ugc_task_id')
                            ->label('Task')
                            ->relationship('task', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('ugc_application_id')
                            ->label('Application')
                            ->relationship('application', 'creator_name')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(function (UgcApplication $record): string {
                                if (filled($record->creator_email)) {
                                    return sprintf('%s (%s)', $record->creator_name, $record->creator_email);
                                }

                                return $record->creator_name;
                            })
                            ->helperText('Optional link to the originating application.')
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->options(self::statusOptions())
                            ->enum(UgcSubmissionStatus::class)
                            ->required(),
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submitted at')
                            ->seconds(false),
                    ]),
                    Forms\Components\TextInput::make('content_url')
                        ->label('Content URL')
                        ->url()
                        ->required()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')
                        ->rows(4)
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
                Tables\Columns\TextColumn::make('application.creator_name')
                    ->label('Application')
                    ->toggleable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('content_url')
                    ->label('Content')
                    ->url(fn (UgcSubmission $record): ?string => $record->content_url)
                    ->limit(30)
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => UgcSubmissionStatus::Submitted->value,
                        'success' => UgcSubmissionStatus::Approved->value,
                        'danger' => UgcSubmissionStatus::Rejected->value,
                        'gray' => UgcSubmissionStatus::RevisionsRequested->value,
                    ])
                    ->formatStateUsing(function (UgcSubmissionStatus|string|null $state): string {
                        if ($state instanceof UgcSubmissionStatus) {
                            return $state->label();
                        }

                        if (is_string($state) && $state !== '') {
                            return UgcSubmissionStatus::from($state)->label();
                        }

                        return UgcSubmissionStatus::Submitted->label();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted at')
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
            'index' => Pages\ListUgcSubmissions::route('/'),
            'create' => Pages\CreateUgcSubmission::route('/create'),
            'edit' => Pages\EditUgcSubmission::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function statusOptions(): array
    {
        return Arr::mapWithKeys(UgcSubmissionStatus::cases(), fn (UgcSubmissionStatus $status): array => [
            $status->value => $status->label(),
        ]);
    }
}
