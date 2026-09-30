<?php
namespace App\Providers\Filament;
use Filament\Panel;
use Filament\PanelProvider;
class AdminPanelProvider extends PanelProvider {
 public function panel(Panel $panel): Panel {
  return $panel->default()->id('admin')->path('admin')->login()->authGuard('admin')
   ->brandName('Os Jogos do Agricultor · Atelier')->colors(['primary'=>\Filament\Support\Colors\Color::Emerald])
   ->viteTheme('resources/css/filament/admin/theme.css')
   ->discoverResources(in:app_path('Filament/Resources'),for:'App\\Filament\\Resources')
   ->navigationGroups(['1 · Concevoir','2 · Chiffrer','3 · Mobiliser','4 · Préparer','5 · Exploiter','6 · Clôturer'])
   ->pages([\App\Filament\Pages\UnitCosting::class,\App\Filament\Pages\Dashboard::class,\App\Filament\Pages\SitePlan::class,\App\Filament\Pages\GeographicSite::class])
   ->middleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Filament\Http\Middleware\AuthenticateSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \Filament\Http\Middleware\DisableBladeIconComponents::class,
    \Filament\Http\Middleware\DispatchServingFilamentEvent::class,
    \App\Http\Middleware\PublicContext::class,
   ])->authMiddleware([\Filament\Http\Middleware\Authenticate::class]);
 }
}
