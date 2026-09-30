<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,FreguesiaInvitation,Team};
use App\Domain\Promotion\PublicMobilization;
class MobilizationController {
 private function render(EventProject $project,string $view,array $data=[]){abort_unless($project->is_public,404);return response()->view('public.mobilization.'.$view,['project'=>$project]+$data)->header('Cache-Control','private, no-store')->header('X-Robots-Tag',app()->environment('local')?'noindex, nofollow':'index, follow');}
 public function communes(EventProject $project){return $this->render($project,'communes',['communes'=>app(PublicMobilization::class)->communes($project)]);}
 public function commune(EventProject $project,FreguesiaInvitation $commune){abort_unless($commune->event_project_id===$project->id&&$commune->is_public,404);return $this->render($project,'commune',['commune'=>$commune,'teams'=>app(PublicMobilization::class)->teams($project)->where('freguesia_invitation_id',$commune->id)]);}
 public function teams(EventProject $project){return $this->render($project,'teams',['teams'=>app(PublicMobilization::class)->teams($project)]);}
 public function team(EventProject $project,Team $team){abort_unless(app(PublicMobilization::class)->teams($project)->contains('id',$team->id),404);return $this->render($project,'team',['team'=>$team,'commune'=>FreguesiaInvitation::whereKey($team->freguesia_invitation_id)->where('event_project_id',$project->id)->where('is_public',true)->first()]);}
 public function relays(EventProject $project){$relays=app(PublicMobilization::class)->relays($project);return $this->render($project,'relays',['relays'=>$relays,'markers'=>app(PublicMobilization::class)->map($relays)]);}
 public function sponsors(EventProject $project){$d=app(PublicMobilization::class);return $this->render($project,'sponsors',['sponsors'=>$d->sponsors($project),'patrons'=>$d->patrons($project)]);}
 public function growth(EventProject $project){return $this->render($project,'growth',['offer'=>$project->offers()->where('template_key','rental-independent')->where('is_public',true)->first()]);}
}
