<?php
namespace Database\Seeders;

use App\Models\EventProject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalVocabularySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $project = EventProject::where('slug', 'os-jogos-do-agricultor')->first();
            if (! $project) return;
            // Only the known generated phrase in active planning documents. No contract rewrite.
            $before = 'Six rôles indispensables (cuisinier, pelliste, tracteur, musicien, comptable, athlète)';
            $after = 'Six rôles indispensables (cuisinier, pelliste, tracteur, musicien, gardien des comptes, athlète)';
            foreach ($project->scenarios()->where('is_archived', false)->get() as $scenario) {
                $text = str_replace($before, $after, $scenario->assumptions ?? '');
                if ($text !== ($scenario->assumptions ?? '')) $scenario->update(['assumptions' => $text]);
            }
            $oldInstruction = 'Utiliser le conducteur ; valider résultats, portions et images avant communiqué.';
            foreach ($project->ideas()->where('template_key', 'event-live')->where('status', 'idea')->where('experiment', $oldInstruction)->get() as $idea) {
                $idea->update(['experiment' => 'Utiliser le déroulé opérationnel ; valider résultats, portions et images avant communiqué.']);
            }
            // Keep the stable accountant role code: existing candidates, votes and assignments survive.
        });
    }
}
