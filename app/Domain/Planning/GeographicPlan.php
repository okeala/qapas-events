<?php
namespace App\Domain\Planning;
use App\Models\EventProject;
final class GeographicPlan {
 public function data(EventProject $p,bool $private=false): ?array {
  if(!$private&&(!$p->is_public||!$p->geo_is_public))return null;
  $features=$p->siteFeatures()->when(!$private,fn($q)=>$q->where('is_public',true))->get();
  $data=['type'=>'FeatureCollection','features'=>$features->map(fn($f)=>['type'=>'Feature','id'=>$f->public_id,'geometry'=>$f->geometry,'properties'=>['name'=>$f->name,'category'=>$f->category,'access'=>$f->access,'activities'=>$f->locations()->with('activity')->get()->filter(fn($l)=>$l->activity&&($private||$l->activity->publicLocationVisible()))->map(fn($l)=>['name'=>$l->activity->name,'role'=>$l->role,'url'=>$private?null:route('activity.show',['project'=>$p->slug,'activity'=>$l->activity->public_id])])->values()->all()]])->all()];
  foreach($data['features'] as &$entry){$feature=$features->firstWhere('public_id',$entry['id']);$entry['properties']['stands']=\App\Models\Stand::where('event_project_id',$p->id)->where('site_feature_id',$feature->id)->where('status','!=','withdrawn')->when(!$private,fn($q)=>$q->where('is_public',true))->get()->map(fn($stand)=>['name'=>$stand->name,'pitch'=>$stand->pitch_number,'url'=>$private?(app()->environment('local')?route('stand.preview',['project'=>$p->slug,'stand'=>$stand->public_id]):\App\Filament\Resources\StandResource::getUrl('edit',['record'=>$stand])):route('stand.show',['project'=>$p->slug,'stand'=>$stand->public_id])])->all();}unset($entry);
  foreach(\App\Models\ActivityLocation::whereHas('activity',fn($q)=>$q->where('event_project_id',$p->id))->whereNotNull('geometry')->with('activity')->get() as $l){
   if(!$private&&(!$l->is_public||!$l->activity->publicLocationVisible()))continue;
   $props=['name'=>$l->name?:$l->activity->name,'category'=>'activity','access'=>$l->access,'activities'=>[['name'=>$l->activity->name,'role'=>$l->role,'url'=>$private?null:route('activity.show',['project'=>$p->slug,'activity'=>$l->activity->public_id])]]];
   if($private)$props+=['activity'=>$l->activity->public_id,'quartel'=>$l->siteFeature->public_id,'additional_quartels'=>\App\Models\SiteFeature::whereIn('id',$l->additional_quartel_ids??[])->pluck('public_id')->all(),'role'=>$l->role,'is_public'=>$l->is_public];
   $data['features'][]=['type'=>'Feature','id'=>'location-'.$l->public_id,'geometry'=>$l->geometry,'properties'=>$props];
  }
  $placements=$private?\App\Models\ActivityLocation::whereHas('activity',fn($q)=>$q->where('event_project_id',$p->id))->get()->map(fn($l)=>['id'=>'location-'.$l->public_id,'geometry'=>$l->geometry,'properties'=>['name'=>$l->name?:$l->activity->name,'activity'=>$l->activity->public_id,'quartel'=>$l->siteFeature->public_id,'additional_quartels'=>\App\Models\SiteFeature::whereIn('id',$l->additional_quartel_ids??[])->pluck('public_id')->all(),'role'=>$l->role,'access'=>$l->access,'is_public'=>$l->is_public]])->all():[];
  return ['placements'=>$placements,'geojson'=>$data,'center'=>config('site_map.center'),'zoom'=>config('site_map.zoom'),'osm'=>config('site_map.osm_url'),'imagery'=>['url'=>config('site_map.imagery_url'),'layers'=>config('site_map.imagery_layer'),'attribution'=>config('site_map.imagery_attribution')]];
 }
}
