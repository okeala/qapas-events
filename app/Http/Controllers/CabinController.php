<?php
namespace App\Http\Controllers;
use App\Models\{EventProject, CabinProject};
class CabinController {
    public function index(EventProject $project) {
        abort_unless($project->is_public && $project->cabin_policy_version && in_array($project->cabin_visibility,['teaser','details'],true),404);
        return $this->render($project,false);
    }
    public function preview(EventProject $project) {
        app(CatalogController::class)->authorizePreview();
        abort_unless($project->cabin_policy_version,404);
        return $this->render($project,true);
    }
    private function render(EventProject $project,bool $preview) {
        $details=$preview||$project->cabin_visibility==='details';
        $cabins=$details?CabinProject::with(['stand.quartel','stand.siteFeature'])->where('event_project_id',$project->id)
            ->when(!$preview,fn($q)=>$q->where('is_public',true)->where('status','!=','withdrawn')->whereHas('stand',fn($s)=>$s->where('event_project_id',$project->id)->where('is_public',true)->where('status','!=','withdrawn')))->orderBy('stand_id')->get():collect();
        return response()->view('public.cabins',compact('project','preview','details','cabins'))
            ->header('Cache-Control',$preview?'private, no-store':'no-cache')
            ->header('X-Robots-Tag',$preview?'noindex, nofollow':'index, follow');
    }
}
