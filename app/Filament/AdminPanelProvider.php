<?php

declare(strict_types=1);

namespace App\Filament;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->middleware([
                'web',
                'workspace',
                // EncryptCookies::class,
                // AddQueuedCookiesToResponse::class,
                // StartSession::class,
                // AuthenticateSession::class,
                // ShareErrorsFromSession::class,
                // VerifyCsrfToken::class,
                // SubstituteBindings::class,
                // DisableBladeIconComponents::class,
                // DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsAdmin::class,
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
                        if ($workspace !== null && $workspace->account_id === $request->user()->account_id) {
                            $request->session()->put('workspace', $workspace->slug);
                        }
                    }),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                Pages\Dashboard::class,
            ]);
    }
}
