<?php
namespace App\Http\Controllers;
use App\Models\{EventProject,PressRelease};
class PressController {
 public function show(EventProject $project,PressRelease $press){abort_unless($press->event_project_id===$project->id&&$press->publiclyAvailable(),404);return view('public.press',compact('project','press'));}
}
