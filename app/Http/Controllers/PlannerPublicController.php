<?php

namespace App\Http\Controllers;

use App\Models\PlannerEvent;
use App\Services\Events\PlanExport;
use App\Services\Events\PublicPlan;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PlannerPublicController extends Controller
{
    public function index()
    {
        $demo = app()->environment('local', 'testing') && config('event_planner.demo_enabled') && in_array(request()->getHost(), ['localhost', '127.0.0.1', '[::1]', '::1'], true);
        $events = PlannerEvent::query()->whereNotNull('published_revision_id')->when(! $demo, fn ($q) => $q->where('is_demo', false))->get();
        $plans = $events->map(fn ($e) => app(PublicPlan::class)->read($e));

        return view('planner.index', compact('plans'));
    }

    public function show(PlannerEvent $event)
    {
        $plan = app(PublicPlan::class)->read($event);

        return response()->view('planner.show', compact('plan', 'event'))->header('Cache-Control', 'no-store');
    }

    public function data(PlannerEvent $event)
    {
        return response()->json(app(PublicPlan::class)->read($event))->header('Cache-Control', 'no-store');
    }

    public function geojson(PlannerEvent $event)
    {
        $plan = app(PublicPlan::class)->read($event);

        return response()->json($plan['plan'])->header('Content-Disposition', 'attachment; filename="'.$event->slug.'-plan.geojson"')->header('Cache-Control', 'no-store');
    }

    public function poster(PlannerEvent $event)
    {
        $plan = app(PublicPlan::class)->read($event);
        $qr = (new QRCode(new QROptions(['outputType' => QRCode::OUTPUT_MARKUP_SVG, 'outputBase64' => true])))->render(route('events.show', $event->slug));

        return response()->view('planner.poster', compact('plan', 'event', 'qr'))->header('Cache-Control', 'no-store');
    }

    public function svg(PlannerEvent $event)
    {
        $svg = app(PlanExport::class)->svg(app(PublicPlan::class)->read($event));

        return response($svg)->header('Content-Type', 'image/svg+xml')->header('Content-Disposition', 'attachment; filename="'.$event->slug.'-plan.svg"')->header('Cache-Control', 'no-store');
    }

    public function pdf(PlannerEvent $event)
    {
        $pdf = app(PlanExport::class)->pdf(app(PublicPlan::class)->read($event));

        return response($pdf)->header('Content-Type', 'application/pdf')->header('Content-Disposition', 'attachment; filename="'.$event->slug.'-plan.pdf"')->header('Cache-Control', 'no-store');
    }

    public function candidate(PlannerEvent $event, string $offer)
    {
        $plan = app(PublicPlan::class)->read($event);
        $public = collect($plan['offers'])->firstWhere('uuid', $offer);
        abort_unless($public && $public['available'], 404);

        return view('planner.candidate', compact('event', 'plan', 'public'));
    }

    public function submitCandidate(Request $request, PlannerEvent $event, string $offer)
    {
        $plan = app(PublicPlan::class)->read($event);
        $public = collect($plan['offers'])->firstWhere('uuid', $offer);
        abort_unless($public && $public['available'], 422, 'Cette offre n’accepte plus de propositions.');
        $request->validate(['proposal' => 'required|string|max:4000', 'acknowledged' => 'accepted']);
        abort(403, 'Le parcours de candidature exige une identité vérifiée ; il n’est pas activé sur cette installation.');
    }
}
