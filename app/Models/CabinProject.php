<?php
namespace App\Models;

use App\Domain\Cabins\CabinRules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\{Rule, ValidationException};

class CabinProject extends Record
{
    protected $attributes = ['supply_mode'=>'team_build','status'=>'concept','width_mm'=>2400,'depth_mm'=>2400,'height_mm'=>2400,'frame_diameter_mm'=>30,'materials'=>'[]','rental_pricing'=>'unpriced','is_public'=>false];
    protected function casts(): array { return ['materials'=>'array','is_public'=>'boolean','follow_up_on'=>'date','reviewed_at'=>'datetime','structural_reviewed_on'=>'date']; }
    public function eventProject() { return $this->belongsTo(EventProject::class); }
    public function stand() { return $this->belongsTo(Stand::class); }
    public function costLine() { return $this->belongsTo(BudgetLine::class,'cost_line_id'); }
    public function rentalLine() { return $this->belongsTo(BudgetLine::class,'rental_line_id'); }

    public function installationFingerprint(): string
    {
        $stand = Stand::with(['quartel','siteFeature'])->find($this->stand_id);
        return hash('sha256', json_encode([
            'stand'=>$stand?->only(['event_project_id','kind','status','quartel_id','site_feature_id','pitch_number','zone']),
            'quartel'=>$stand?->quartel?->only(['event_project_id','geometry','access']),
            'feature'=>$stand?->siteFeature?->only(['event_project_id','geometry','access']),
        ], JSON_THROW_ON_ERROR));
    }
    public function received(): bool
    {
        return $this->status==='received' && $this->reviewed_by && $this->reviewed_at && !$this->reviewed_at->isFuture()
            && filled($this->installation_hash) && hash_equals($this->installation_hash, $this->installationFingerprint());
    }
    public function extended(): bool { return $this->depth_mm>2400; }
    public function publicSummary(): ?string { return $this->{'summary_'.(app()->getLocale()==='pt'?'pt':'fr')} ?: $this->summary_fr ?: $this->summary_pt; }

    protected static function booted(): void
    {
        parent::booted();
        static::saving(function (self $c): void {
            Validator::make($c->getAttributes(), [
                'name'=>'required|string|max:200', 'event_project_id'=>'required|integer|exists:event_projects,id',
                'supply_mode'=>'required|in:team_build,qapas_rental', 'status'=>['required',Rule::in(array_keys(CabinRules::STATUSES))],
                'width_mm'=>'required|integer|in:2400','depth_mm'=>'required|integer|between:2400,240000','height_mm'=>'required|integer|in:2400',
                'frame_diameter_mm'=>'required|integer|between:1,2000','frame_spacing_mm'=>'nullable|integer|between:1,240000','structural_reviewer'=>'nullable|string|max:255',
                'structural_reviewed_on'=>'nullable|date|before_or_equal:today','structural_evidence'=>'nullable|string|max:10000',
                'rental_pricing'=>'required|in:unpriced,included,extra',
                'deposit_cents'=>'nullable|integer|between:0,100000000', 'participant_cost_cents'=>'nullable|integer|between:0,100000000',
                'owner'=>'nullable|string|max:255','follow_up_owner'=>'nullable|string|max:255','follow_up_on'=>'nullable|date',
                'harvest_origin'=>'nullable|string|max:5000','control_plan'=>'nullable|string|max:10000',
                'follow_up_notes'=>'nullable|string|max:10000','reception_evidence'=>'nullable|string|max:10000',
                'rental_terms'=>'nullable|string|max:10000','participant_cost_evidence'=>'nullable|string|max:5000',
                'summary_fr'=>'nullable|string|max:5000','summary_pt'=>'nullable|string|max:5000',
            ])->validate();
            $stand = Stand::whereKey($c->stand_id)->where('event_project_id',$c->event_project_id)->first();
            if (!$stand) throw ValidationException::withMessages(['stand_id'=>'Choisir un stand de cette édition.']);
            if ($c->supply_mode!==($stand->kind==='village'?'team_build':'qapas_rental')) throw ValidationException::withMessages(['supply_mode'=>'Freguesia : fabrication par l’équipe. Indépendant : cabane QAPAS en location.']);
            if ($c->exists && $c->isDirty(['event_project_id','stand_id','supply_mode'])) throw ValidationException::withMessages(['stand_id'=>'Conserver le stand et son mode de fourniture.']);
            if (self::where('stand_id',$c->stand_id)->when($c->exists,fn($q)=>$q->whereKeyNot($c->id))->exists()) throw ValidationException::withMessages(['stand_id'=>'Ce stand a déjà son dossier de cabane.']);
            CabinRules::validateMaterials($c->materials ?? []);
            foreach (['cost_line_id'=>'cost','rental_line_id'=>'revenue'] as $key=>$kind) {
                if (!$c->$key) continue;
                if ($c->supply_mode!=='qapas_rental' || !BudgetLine::whereKey($c->$key)->where('kind',$kind)->where('stand_id',$c->stand_id)->whereNull('superseded_by_id')->whereHas('scenario',fn($q)=>$q->where('event_project_id',$c->event_project_id)->where('is_archived',false))->exists()) throw ValidationException::withMessages([$key=>'Choisir un poste du même stand QAPAS dans un scénario actif de cette édition.']);
            }
            if ($c->cost_line_id && $c->rental_line_id && BudgetLine::find($c->cost_line_id)->scenario_id!==BudgetLine::find($c->rental_line_id)->scenario_id) throw ValidationException::withMessages(['rental_line_id'=>'Fabrication et location doivent appartenir au même scénario.']);
            if ($c->rental_pricing!=='extra' && $c->rental_line_id && BudgetLine::find($c->rental_line_id)?->forecast_quantity>0) throw ValidationException::withMessages(['rental_pricing'=>'Une location incluse ou non tarifée ne crée pas une seconde recette. Désactiver le supplément dans le budget.']);
            if ($c->rental_pricing==='extra' && (!$c->rental_line_id || blank($c->rental_terms))) throw ValidationException::withMessages(['rental_pricing'=>'Décrire le supplément et le relier à sa ligne budgétaire, hors prix déjà inclus.']);
            if ($c->exists && $c->isDirty(['width_mm','depth_mm','height_mm','materials','frame_diameter_mm','frame_spacing_mm'])) $c->structural_reviewed_on=null;
            if ($c->exists && $c->isDirty(['reviewed_at','reviewed_by','installation_hash'])) throw ValidationException::withMessages(['status'=>'La réception est enregistrée par la transition de statut.']);
            if ($c->exists && $c->getOriginal('status')==='received' && $c->isDirty(['materials','width_mm','depth_mm','height_mm','frame_diameter_mm','frame_spacing_mm','structural_reviewer','structural_reviewed_on','structural_evidence','owner','harvest_origin','control_plan','follow_up_owner','follow_up_on','reception_evidence'])) $c->status='ready';
            if ($c->status==='received' && (!$c->exists || $c->getOriginal('status')!=='received')) {
                abort_unless(auth('admin')->user()?->is_active,403);
                foreach (['frame','connectors','roof','cladding','lashings'] as $part) if (!collect($c->materials)->contains('part',$part)) throw ValidationException::withMessages(['materials'=>'Documenter tubes, raccords d’échafaudage, couverture, bardage et ligatures naturelles.']);
                foreach (['owner','harvest_origin','control_plan','follow_up_owner','follow_up_on','reception_evidence'] as $field) if (blank($c->$field)) throw ValidationException::withMessages([$field=>'Réception : responsable, origine, prévention de dispersion, suivi et contrôle sur site requis.']);
                if ($stand->status==='withdrawn' || !$stand->quartel_id || blank($stand->pitch_number)) throw ValidationException::withMessages(['stand_id'=>'Implanter le stand actif dans un quartel avec un numéro avant réception.']);
                if ($c->extended()) foreach (['frame_spacing_mm','structural_reviewer','structural_reviewed_on','structural_evidence'] as $field) if (blank($c->$field)) throw ValidationException::withMessages([$field=>'Extension : entraxe issu du dimensionnement, auteur, date et note de calcul couvrant emprise, charges, assemblages et ancrages requis.']);
                $c->reviewed_by=auth('admin')->id(); $c->reviewed_at=now(); $c->installation_hash=$c->installationFingerprint();
            } elseif ($c->status!=='received') {
                $c->reviewed_by=null; $c->reviewed_at=null; $c->installation_hash=null;
            }
        });
    }
}
