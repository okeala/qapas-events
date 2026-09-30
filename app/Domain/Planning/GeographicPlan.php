<?php
namespace App\Domain\Planning;
use App\Models\EventProject;
final class GeographicPlan {
 public function data(EventProject $p,bool $private=false): ?array {
  if(!$private&&(!$p->is_public||!$p->geo_is_public))return null;
  $features=$p->siteFeatures()->when(!$private,fn($q)=>$q->where('is_public',true))->get();
  $data=['type'=>'FeatureCollection','features'=>$features->map(fn($f)=>['type'=>'Feature','id'=>$f->public_id,'geometry'=>$f->geometry,'properties'=>['name'=>$f->name,'category'=>$f->category,'access'=>$f->access,'activities'=>$f->locations()->with('activity')->get()->filter(fn($l)=>$l->activity&&($private||$l->activity->publicLocationVisible()))->map(fn($l)=>['name'=>$l->activity->name,'role'=>$l->role,'url'=>$private?null:route('activity.show',['project'=>$p->slug,'activity'=>$l->activity->public_id])])->values()->all()]])->all()];
  foreach(\App\Models\ActivityLocation::whereHas('activity',fn($q)=>$q->where('event_project_id',$p->id))->whereNotNull('geometry')->with('activity')->get() as $l){
   if(!$private&&(!$l->is_public||!$l->activity->publicLocationVisible()))continue;
   $props=['name'=>$l->name?:$l->activity->name,'category'=>'activity','access'=>$l->access,'activities'=>[['name'=>$l->activity->name,'role'=>$l->role,'url'=>$private?null:route('activity.show',['project'=>$p->slug,'activity'=>$l->activity->public_id])]]];
   if($private)$props+=['activity'=>$l->activity->public_id,'quartel'=>$l->siteFeature->public_id,'additional_quartels'=>\App\Models\SiteFeature::whereIn('id',$l->additional_quartel_ids??[])->pluck('public_id')->all(),'role'=>$l->role,'is_public'=>$l->is_public];
   $data['features'][]=['type'=>'Feature','id'=>'location-'.$l->public_id,'geometry'=>$l->geometry,'properties'=>$props];
  }
  return ['geojson'=>$data,'center'=>config('site_map.center'),'zoom'=>config('site_map.zoom'),'osm'=>config('site_map.osm_url'),'imagery'=>['url'=>config('site_map.imagery_url'),'layers'=>config('site_map.imagery_layer'),'attribution'=>config('site_map.imagery_attribution')]];
 }
}
