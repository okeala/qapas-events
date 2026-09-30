<?php
namespace App\Domain\Planning;
use App\Models\EventProject;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class SitePlan {
 public static function validPath(?string $path): bool {return is_string($path)&&preg_match('#^event-plans/[a-f0-9-]{36}\.png$#D',$path)===1;}
 public function upload($upload): string {
  if(!function_exists('imagecreatefromstring')) throw ValidationException::withMessages(['planUpload'=>'Installer l’extension PHP GD pour importer un plan.']);
  $bytes=file_get_contents($upload->getRealPath());$info=@getimagesizefromstring($bytes);
  if(!$info||!in_array($info[2],[IMAGETYPE_PNG,IMAGETYPE_JPEG,IMAGETYPE_WEBP],true)||$info[0]>4000||$info[1]>4000||$info[0]*$info[1]>12000000) throw ValidationException::withMessages(['planUpload'=>'Image PNG/JPEG/WebP : 4 000 pixels maximum par côté, 12 mégapixels maximum.']);
  $image=@imagecreatefromstring($bytes);if(!$image) throw ValidationException::withMessages(['planUpload'=>'Image non lisible.']);
  // Re-encode on the server: metadata and original filename never become public.
  ob_start();imagepng($image);$clean=ob_get_clean();imagedestroy($image);
  $path='event-plans/'.Str::uuid().'.png';if(!Storage::disk('local')->put($path,$clean)) throw ValidationException::withMessages(['planUpload'=>'Enregistrement du plan impossible.']);
  return $path;
 }
 public function dimensions(EventProject $p): ?array {
  if(!self::validPath($p->plan_image)||!Storage::disk('local')->exists($p->plan_image)) return null;
  $size=@getimagesize(Storage::disk('local')->path($p->plan_image));return $size?['width'=>$size[0],'height'=>$size[1]]:null;
 }
 public function data(EventProject $p,bool $private=false): ?array {
  if(!$private&&(!$p->is_public||!$p->plan_is_public)) return null;
  $dimensions=$this->dimensions($p);if(!$dimensions) return null;
  $terraces=$p->terraces()->when(!$private,fn($q)=>$q->where('is_public',true))->get();
  $activities=$p->activities()->where('status','!=','archived')->when(!$private,fn($q)=>$q->where('is_public',true))->get();
  return $dimensions+[
   'image'=>route($private?'plan.private-image':'plan.image',['project'=>$p->public_id]),
   'terraces'=>$terraces->map(fn($t)=>['id'=>$t->public_id,'name'=>$t->name,'boundary'=>$t->boundary,'access'=>$t->access])->values()->all(),
   'activities'=>$activities->filter(fn($a)=>$a->map_x!==null&&$terraces->contains('id',$a->terrace_id))->map(fn($a)=>['id'=>$a->public_id,'name'=>$a->name,'x'=>$a->map_x,'y'=>$a->map_y,'url'=>$private?null:route('activity.show',['project'=>$p->slug,'activity'=>$a->public_id])])->values()->all(),
  ];
 }
}
