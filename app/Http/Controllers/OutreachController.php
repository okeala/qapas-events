<?php
namespace App\Http\Controllers;
use App\Models\OutreachVisit;
class OutreachController {public function letter(OutreachVisit $visit){abort_unless(auth('admin')->user()?->is_active,403);return response()->view('workspace.outreach-letter',compact('visit'))->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex');}}
