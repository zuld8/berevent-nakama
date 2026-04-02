<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\RedirectAdminLoginToStorefront;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Nakama Project Hub')
            ->favicon(asset('favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->sidebarWidth('18rem')
            ->collapsedSidebarWidth('4.25rem')
            ->colors([
                'primary' => Color::Teal,
                'danger' => Color::Rose,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->maxContentWidth('full')
            ->renderHook(
                'panels::styles.after',
                fn () => new \Illuminate\Support\HtmlString(
                    '<link rel="stylesheet" href="' . asset('css/admin-theme.css') . '?v=' . filemtime(public_path('css/admin-theme.css')) . '">'
                )
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\HelloWidget::class,
                Widgets\StatsOverview::class,
                Widgets\RevenueChart::class,
                Widgets\LatestOrders::class,
                Widgets\LatestDonations::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Utama')->icon('heroicon-o-home')->collapsed(false),
                NavigationGroup::make('Events')->icon('heroicon-o-ticket'),
                NavigationGroup::make('Donasi')->icon('heroicon-o-heart'),
                NavigationGroup::make('Manajemen')->icon('heroicon-o-cog-6-tooth'),
                NavigationGroup::make('Integrasi')->icon('heroicon-o-link'),
                NavigationGroup::make('Laporan')->icon('heroicon-o-chart-bar'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                EnsureAdmin::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
