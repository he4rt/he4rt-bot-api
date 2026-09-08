<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\FilamentPanel;
use App\Http\Middleware\SetApplicationLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use He4rt\PanelApp\Pages\EventPage;
use He4rt\PanelApp\Pages\EventsPage;
use He4rt\PanelApp\Pages\LoginPage;
use He4rt\PanelApp\Pages\MyEventsPage;
use He4rt\PanelApp\Pages\ProfilePage;
use He4rt\PanelApp\Pages\ThreadPage;
use He4rt\PanelApp\Pages\TimelinePage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public FilamentPanel $panelId = FilamentPanel::App;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id($this->panelId->value)
            ->path($this->panelId->value)
            ->login(LoginPage::class)
            ->topbar(condition: false)
            ->colors([
                'primary' => Color::Purple,
                'gray' => Color::Zinc,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->pages([
                TimelinePage::class,
                EventsPage::class,
                MyEventsPage::class,
                EventPage::class,
                ThreadPage::class,
                ProfilePage::class,
            ])
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->label(__('app.user_menu.my_profile'))
                    ->url(ProfilePage::getUrl())
                    ->icon(null),
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
