<?php
namespace App\Domain\Planning;
use App\Models\EventProject;
final class GeographicPlan {
 public function data(EventProject $p,bool $private=false): ?array {
  if(!$private&&(!$p->is_public||!$p->geo_is_public))return null;
  $features=$p->siteFeatures()->when(!$private,fn($q)=>$q->where('is_public',true))->get();
  $data=['type'=>'FeatureCollection','features'=>$features->map(fn($f)=>['type'=>'Feature','id'=>$f->public_id,'geometry'=>$f->geometry,'properties'=>['name'=>$f->name,'category'=>$f->category,'access'=>$f->access,'activities'=>$f->locations()->with('activity')->get()->filter(fn($l)=>$l->activity&&($private||($l->activity->is_public&&$l->activity->publication_level!=='hidden'&&$l->activity->status!=='archived')))->map(fn($l)=>['name'=>$l->activity->name,'role'=>$l->role,'url'=>$private?null:route('activity.show',['project'=>$p->slug,'activity'=>$l->activity->public_id])])->values()->all()]])->all()];
  return ['geojson'=>$data,'center'=>config('site_map.center'),'zoom'=>config('site_map.zoom'),'osm'=>config('site_map.osm_url'),'imagery'=>['url'=>config('site_map.imagery_url'),'layers'=>config('site_map.imagery_layer'),'attribution'=>config('site_map.imagery_attribution')]];
 }
}
