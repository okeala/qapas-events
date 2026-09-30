<?php
namespace App\Http\Controllers;
use App\Models\EventBadge;
class BadgeController {
 public function verify(EventBadge $badge){
  abort_unless($badge->eventProject->is_public,404);
  // Explicit projection: never pass the holder, source registry or private evidence to the view.
  $data=['event'=>$badge->eventProject->name,'edition'=>$badge->eventProject->edition_year,'serial'=>$badge->serial,'role'=>$badge->role,'valid'=>$badge->valid(),'revoked'=>$badge->status==='revoked'];
  return response()->view('public.badge',$data)->header('Cache-Control','no-store')->header('X-Robots-Tag','noindex, nofollow')->header('Referrer-Policy','no-referrer');
 }
 public function print(EventBadge $badge){abort_unless(auth('admin')->user()?->is_active,403);return response()->view('workspace.badge',compact('badge'))->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex, nofollow');}
}
