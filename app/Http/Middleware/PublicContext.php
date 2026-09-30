<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class PublicContext {
 public function handle(Request $request, Closure $next) {
  $locale=$request->session()->get('locale',config('app.locale'));
  app()->setLocale(in_array($locale,config('qapas_application.locales'),true)?$locale:'fr');
  $response=$next($request);
  $response->headers->set('X-Content-Type-Options','nosniff');
  $response->headers->set('Referrer-Policy','strict-origin-when-cross-origin');
  $response->headers->set('X-Frame-Options','SAMEORIGIN');
  $response->headers->set('Permissions-Policy','camera=(), microphone=(), geolocation=()');
  $response->headers->set('Cache-Control','private, no-store');
  if ($request->isSecure()) $response->headers->set('Strict-Transport-Security','max-age=31536000');
  return $response;
 }
}
