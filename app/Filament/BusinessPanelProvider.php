<?php

declare(strict_types=1);

namespace App\Filament;

use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Http\Request;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('business')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->middleware([
                'web',
                'workspace',
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                Action::make('workspace')
                    ->label(fn (): string => currentWorkspace()?->name ?? 'Workspace')
                    ->icon('heroicon-m-rectangle-stack')
                    ->form([
                        Select::make('workspace')
                            ->label('Workspace')
                            ->options(fn (): array => auth()->user()?->account?->workspaces()?->pluck('name', 'slug')?->toArray() ?? [])
                            ->default(fn (): ?string => session('workspace')),
                    ])
                    ->action(function (array $data, Request $request): void {
                        $workspace = Workspace::where('slug', $data['workspace'])->first();
                        if ($workspace !== null && $workspace->account_id === $request->user()?->account_id) {
                            $request->session()->put('workspace', $workspace->slug);
                        }
                    }),
            ])
            ->discoverResources(in: app_path('Filament/Business/Resources'), for: 'App\\Filament\\Business\\Resources')
            ->discoverPages(in: app_path('Filament/Business/Pages'), for: 'App\\Filament\\Business\\Pages')
            ->discoverWidgets(in: app_path('Filament/Business/Widgets'), for: 'App\\Filament\\Business\\Widgets')
            ->pages([
                \App\Filament\Business\Pages\Dashboard::class,
            ]);
    }
}
