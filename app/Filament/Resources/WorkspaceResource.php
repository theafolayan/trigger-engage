<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WorkspaceResource\Pages\CreateWorkspace;
use App\Filament\Resources\WorkspaceResource\Pages\EditWorkspace;
use App\Filament\Resources\WorkspaceResource\Pages\ListWorkspaces;
use App\Models\Workspace;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class WorkspaceResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-building-office';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('slug')->required(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('slug')->sortable()->searchable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkspaces::route('/workspaces'),
            'create' => CreateWorkspace::route('/workspaces/create'),
            'edit' => EditWorkspace::route('/workspaces/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('account_id', auth()->user()->account_id);
    }
}
