<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\FilamentPanel;
use App\Filament\Pages\Login;
use App\Http\Middleware\SetApplicationLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use He4rt\PanelAdmin\Pages\Dashboard;
use He4rt\PanelApp\Pages\ProfilePage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    private FilamentPanel $panelId = FilamentPanel::Admin;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->path($this->panelId->value)
            ->id($this->panelId->value)
            ->login(Login::class)
            ->colors(static function (): array {
                $colors = Color::all();

                unset($colors['gray']);

                return [
                    'primary' => Color::Purple,
                    'gray' => Color::Zinc,
                    ...$colors,
                ];
            })
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetApplicationLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->label(__('app.user_menu.my_profile'))
                    ->url(ProfilePage::getUrl(panel: FilamentPanel::App->value))
                    ->icon(icon: null),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
