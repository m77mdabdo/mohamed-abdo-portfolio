<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAdminLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Component;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The dashboard at /admin. Sign-in only (no public registration), Filament's built-in login rate limit,
 * optional authenticator-app two-factor sign-in, Arabic/English with right-to-left support.
 */
class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Labels derived from field names go through lang/ar.json, so the whole dashboard is bilingual.
        Component::configureUsing(fn (Component $c) => method_exists($c, 'translateLabel') ? $c->translateLabel() : null);
        Column::configureUsing(fn (Column $c) => $c->translateLabel());
        BaseFilter::configureUsing(fn (BaseFilter $f) => $f->translateLabel());
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(isSimple: false)
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()])
            ->brandName('Mohamed Abdo · CMS')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::hex('#C15B46'),
                'info' => Color::hex('#2FB8BF'),
                'gray' => Color::Stone,
            ])
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make(fn () => __('Site content')),
                NavigationGroup::make(fn () => __('Projects')),
                NavigationGroup::make(fn () => __('Lists')),
                NavigationGroup::make(fn () => __('Navigation & texts')),
                NavigationGroup::make(fn () => __('Settings')),
                NavigationGroup::make(fn () => __('Inbox & people')),
            ])
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => Blade::render('filament.locale-switch'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetAdminLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
