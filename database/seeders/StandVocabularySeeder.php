<?php
namespace Database\Seeders;

use App\Domain\Finance\BudgetIdentity;
use App\Models\{BudgetLine, CabinProject, CostConsultation, EditorialPost, EventProject, Sponsorship};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandVocabularySeeder extends Seeder
{
    public function run(): void
    {
        $phrases = json_decode(file_get_contents(database_path('data/stand-vocabulary.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($phrases): void {
            $project = EventProject::where('slug', 'os-jogos-do-agricultor')->first();
            if (! $project) return;

            // Update known generated copy only. Keep technical keys and all relationships stable.
            $rewrite = function (Model $record, array $fields) use ($phrases): void {
                foreach ($fields as $field) {
                    if (is_string($record->$field)) $record->$field = strtr($record->$field, $phrases);
                }
                if ($record instanceof BudgetLine) {
                    // A wording change must not merge two real expenses or create a duplicate identity.
                    if ($record->isDirty('name') && BudgetIdentity::duplicates($record)->isNotEmpty()) {
                        $record->name = $record->getOriginal('name');
                    }
                }
                if ($record->isDirty()) $record->save();
                if ($record instanceof BudgetLine && $record->unit === 'cabane') {
                    // Data migration of a label only: do not invalidate a verified price as if
                    // the measurement unit had changed. Normal administrative edits keep that guard.
                    DB::table('budget_lines')->where('id', $record->id)->where('unit', 'cabane')->update(['unit'=>'stand']);
                }
            };

            foreach ($project->scenarios()->where('is_archived', false)->get() as $scenario) {
                foreach ($scenario->budgetLines()->whereNull('superseded_by_id')->get() as $line) {
                    $fields = ['name'];
                    if (! $line->committed_quantity && ! $line->paid_quantity && $line->pricing_status === 'estimate') $fields[] = 'price_source';
                    $rewrite($line, $fields);
                }
            }
            foreach ($project->stands as $stand) $rewrite($stand, ['name', 'needs', 'public_description']);
            foreach (CabinProject::where('event_project_id', $project->id)->get() as $construction) {
                // Never change dimensions, inventories, reception evidence or existing rental terms.
                $rewrite($construction, ['name', 'summary_fr', 'summary_pt']);
            }
            foreach ($project->ideas as $idea) $rewrite($idea, ['name', 'hypothesis', 'experiment']);
            foreach ($project->offers as $offer) $rewrite($offer, ['name', 'summary', 'includes', 'excludes', 'delivery']);
            foreach (Sponsorship::where('event_project_id', $project->id)->get() as $sponsorship) {
                $fields = ['catalog_fr', 'catalog_pt'];
                if ($sponsorship->status === 'prospecting' && blank($sponsorship->agreement_evidence)) $fields[] = 'pitch';
                $rewrite($sponsorship, $fields);
            }
            // Sent correspondence, received quotes, published articles and contractual evidence are history.
            foreach (CostConsultation::where('event_project_id', $project->id)->whereNull('sent_at')->whereDoesntHave('quotes')->get() as $consultation) {
                $rewrite($consultation, ['name', 'subject_fr', 'subject_pt', 'body_fr', 'body_pt']);
            }
            foreach (EditorialPost::where('event_project_id', $project->id)->where('status', 'draft')->get() as $post) {
                $rewrite($post, ['name', 'title_pt', 'body_fr', 'body_pt']);
            }
        });
    }
}
