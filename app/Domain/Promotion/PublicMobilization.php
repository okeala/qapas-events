<?php
namespace App\Domain\Promotion;
use App\Models\{EventProject,FreguesiaInvitation,Team,StandPartner,Sponsorship};
final class PublicMobilization {
 public function communes(EventProject $p){return FreguesiaInvitation::where('event_project_id',$p->id)->where('is_public',true)->orderBy('municipality')->orderBy('name')->get();}
 public function teams(EventProject $p){return Team::where('event_project_id',$p->id)->where(function($q){$q->where(fn($q)=>$q->where('is_public',true)->where('is_demo',false));if(app()->environment('local'))$q->orWhere('is_demo',true);})->orderBy('freguesia')->orderBy('name')->get();}
 public function relays(EventProject $p){return StandPartner::whereHas('stand',fn($q)=>$q->where('event_project_id',$p->id)->where('is_public',true)->where('status','!=','withdrawn'))->where('is_public',true)->whereNotNull('relay_slot')->where('status','active')->with('stand')->orderBy('name')->get()->filter(fn($r)=>filled($r->mission)&&filled($r->evidence));}
 public function sponsors(EventProject $p){return Sponsorship::where('event_project_id',$p->id)->get()->filter(fn($s)=>$s->visible());}
 public function patrons(EventProject $p){return StandPartner::whereHas('stand',fn($q)=>$q->where('event_project_id',$p->id)->where('is_public',true)->where('status','!=','withdrawn'))->where('is_public',true)->where(fn($q)=>$q->where('main_slot',1)->orWhere('share_units','>',0))->whereIn('status',['agreed','active'])->with('stand')->get()->filter(fn($r)=>filled($r->evidence));}
 public function hasSponsors(EventProject $p): bool {return $this->sponsors($p)->isNotEmpty()||$this->patrons($p)->isNotEmpty();}
 public function cards(EventProject $p,$milestones): array {
  $destinations=['frame'=>'growth','juntas'=>'communes','team'=>'teams','patron'=>'sponsors','relays'=>'relays','communication'=>'communes','funding'=>'growth','growth'=>'growth'];$has=$this->hasSponsors($p);
  return $milestones->map(function($m)use($p,$destinations,$has){$key=$m->template_key;$known=isset($destinations[$key]);return ['title'=>$known?__('mobilization.cards.'.($key==='patron'&&$has?'thanks':$key).'.title'):($m->public_label?:$m->name),'body'=>$known?__('mobilization.cards.'.($key==='patron'&&$has?'thanks':$key).'.body'):null,'url'=>$known?route('mobilization.'.$destinations[$key],['project'=>$p->slug]):null,'link'=>$known?__('mobilization.links.'.$destinations[$key]):null,'done'=>$m->isValidated()];})->all();
 }
 public function map($relays): array {return $relays->filter(fn($r)=>$r->latitude!==null&&$r->longitude!==null&&filled($r->location_evidence))->map(fn($r)=>['name'=>$r->name,'address'=>$r->public_address,'lat'=>(float)$r->latitude,'lng'=>(float)$r->longitude,'anchor'=>'relay-'.$r->public_id])->values()->all();}
}
