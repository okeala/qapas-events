<?php

namespace App\Http\Controllers;

use App\Models\EventScenario;
use App\Services\Events\EventAccess;
use App\Services\Events\PlanWriter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PlannerPlanController extends Controller
{
    public function data(EventScenario $scenario)
    {
        EventAccess::authorize();
        EventAccess::authorizePublic($scenario->event);

        return response()->json(['type' => 'FeatureCollection', 'features' => $scenario->elements()->whereNull('archived_at')->get()->map(fn ($e) => [
            'type' => 'Feature', 'id' => $e->uuid, 'geometry' => $e->geometry,
            'properties' => ['id' => $e->id, 'uuid' => $e->uuid, 'name' => $e->name, 'category' => $e->category, 'subcategory' => $e->subcategory, 'shape' => $e->shape, 'color' => $e->color, 'revision' => $e->revision],
        ])->all()])->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request, EventScenario $scenario)
    {
        EventAccess::authorize();
        EventAccess::authorizePublic($scenario->event);
        $element = $request->input('id') ? $scenario->elements()->findOrFail($request->integer('id')) : null;
        $item = app(PlanWriter::class)->save($scenario, $request->all(), $element);

        return response()->json(['id' => $item->id, 'uuid' => $item->uuid, 'revision' => $item->revision]);
    }

    public function export(EventScenario $scenario)
    {
        EventAccess::authorize();
        EventAccess::authorizePublic($scenario->event);

        return response()->streamDownload(function () use ($scenario) {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['Identifiant', 'Élément', 'Catégorie', 'Nature', 'Libellé', 'TTC centimes', 'Net gestion centimes', 'État', 'Source'], ';', '"', '');
            $safe = fn ($text) => preg_match('/^[=+@\-\t\r]/', (string) $text) ? "'".$text : $text;
            foreach ($scenario->budgetLines()->with('element')->get() as $line) {
                fputcsv($out, [$line->element?->uuid ?? '', $safe($line->element?->name ?? 'Commun'), $safe($line->element?->category ?? ''), $line->kind,
                    $safe($line->label), $line->gross_cents ?? '', $line->net_cents ?? '', $line->status, $safe($line->source_reference ?? '')], ';', '"', '');
            }
            fclose($out);
        }, 'scenario-'.$scenario->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
