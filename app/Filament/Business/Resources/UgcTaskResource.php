<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources;

use App\Enums\UgcTaskStatus;
use App\Filament\Business\Resources\UgcTaskResource\Pages;
use App\Models\UgcTask;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UgcTaskResource extends Resource
{
    protected static ?string $model = UgcTask::class;

    protected static ?string $navigationIcon = 'heroicon-m-clipboard-document-check';

    protected static ?string $navigationGroup = 'UGC';

    protected static ?string $navigationLabel = 'Tasks';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Task details')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', str()->slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Rule $rule): Rule {
                                $workspaceId = currentWorkspace()?->id;

                                return $workspaceId === null
                                    ? $rule
                                    : $rule->where('workspace_id', $workspaceId);
                            }),
                        Forms\Components\Select::make('status')
                            ->options(self::statusOptions())
                            ->enum(UgcTaskStatus::class)
                            ->required(),
                        Forms\Components\TextInput::make('reward')
                            ->label('Reward')
                            ->maxLength(255),
                    ]),
                    Forms\Components\Textarea::make('brief')
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('requirements')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
            Section::make('Schedule')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish at')
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('deadline_at')
                            ->label('Deadline')
                            ->seconds(false),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => UgcTaskStatus::Draft->value,
                        'success' => UgcTaskStatus::Published->value,
                        'danger' => UgcTaskStatus::Closed->value,
                    ])
                    ->formatStateUsing(function (UgcTaskStatus|string|null $state): string {
                        if ($state instanceof UgcTaskStatus) {
                            return $state->label();
                        }

                        if (is_string($state) && $state !== '') {
                            return UgcTaskStatus::from($state)->label();
                        }

                        return UgcTaskStatus::Draft->label();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('reward')
                    ->toggleable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline_at')
                    ->label('Deadline')
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
            'index' => Pages\ListUgcTasks::route('/'),
            'create' => Pages\CreateUgcTask::route('/create'),
            'edit' => Pages\EditUgcTask::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function statusOptions(): array
    {
        return Arr::mapWithKeys(UgcTaskStatus::cases(), fn (UgcTaskStatus $status): array => [
            $status->value => $status->label(),
        ]);
    }
}
