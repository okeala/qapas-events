<?php
namespace App\Filament\Pages;
use App\Models\{EventProject,SiteFeature};
use App\Domain\Planning\{GeoImport,GeographicPlan};
use Illuminate\Support\Facades\{DB,Validator};
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
class GeographicSite extends \Filament\Pages\Page {
 use WithFileUploads;
 protected static ?string $title='Plan du site';
 protected static ?string $navigationLabel='Plan du site';
 protected static string|\UnitEnum|null $navigationGroup='1 · Concevoir';
 protected static ?int $navigationSort=25;
 protected string $view='filament.pages.geographic-site';
 public ?string $projectId=null;
 public $importFile=null;
 public string $importCategory='quartel';
 public int $revision=0;
 #[Locked] public array $previewFeatures=[];
 #[Locked] public ?string $previewProject=null;
 public static function canAccess(): bool {return auth('admin')->user()?->is_active===true;}
 public function mount(): void {$this->projectId=EventProject::orderBy('id')->value('public_id');}
 public function project(): ?EventProject {abort_unless(self::canAccess(),403);return EventProject::where('public_id',$this->projectId)->first();}
 private function authorizedProject(): EventProject {abort_unless(self::canAccess(),403);return $this->project()??abort(404);}
 public function previewImport(): void {
  $p=$this->authorizedProject();$this->validate(['importFile'=>'required|file|max:2048']);$extension=strtolower($this->importFile->getClientOriginalExtension());
  if(!in_array($extension,['json','geojson','kml']))throw \Illuminate\Validation\ValidationException::withMessages(['importFile'=>'Choisir un GeoJSON ou un KML.']);
  $this->previewFeatures=app(GeoImport::class)->parse(file_get_contents($this->importFile->getRealPath()),$extension);$this->previewProject=$p->public_id;
 }
 public function commitImport(): void {
  $p=$this->authorizedProject();abort_unless($this->previewProject===$p->public_id&&count($this->previewFeatures)>0,422);
  $this->validate(['importCategory'=>['required',\Illuminate\Validation\Rule::in(array_keys(SiteFeature::CATEGORIES))]]);
  DB::transaction(function()use($p){foreach($this->previewFeatures as $f)$p->siteFeatures()->create($f+['category'=>$this->importCategory]);});
  $this->previewFeatures=[];$this->previewProject=null;$this->importFile=null;$this->revision++;
 }
 public function saveFeature(string $name,string $category,array $geometry,?string $id=null): void {
  $p=$this->authorizedProject();Validator::make(['name'=>$name],['name'=>'required|string|max:120'])->validate();
  $f=$id?$p->siteFeatures()->where('public_id',$id)->firstOrFail():new SiteFeature(['event_project_id'=>$p->id]);
  $f->fill(compact('name','category','geometry'));$f->save();$this->revision++;
 }
 public function savePlacement(string $name,string $activity,string $quartel,array $additional,array $geometry,string $role,string $access,bool $public,?string $id=null): void {
  $p=$this->authorizedProject();Validator::make(compact('name','additional'),['name'=>'required|string|max:120','additional'=>'array|max:19','additional.*'=>'uuid|distinct'])->validate();
  $a=$p->activities()->where('public_id',$activity)->firstOrFail();$q=$p->siteFeatures()->where('public_id',$quartel)->where('category','quartel')->firstOrFail();
  $extra=$p->siteFeatures()->whereIn('public_id',$additional)->where('category','quartel')->pluck('id');abort_unless($extra->count()===count($additional),422);
  $l=$id?\App\Models\ActivityLocation::whereHas('activity',fn($query)=>$query->where('event_project_id',$p->id))->where('public_id',$id)->firstOrFail():new \App\Models\ActivityLocation();
  $l->fill(['name'=>$name,'activity_id'=>$a->id,'site_feature_id'=>$q->id,'additional_quartel_ids'=>$extra->all(),'geometry'=>$geometry,'role'=>$role,'access'=>$access,'is_public'=>$public]);$l->save();$this->revision++;
 }
 public function exportGeojson(){
  $p=$this->authorizedProject();$json=json_encode(app(GeographicPlan::class)->data($p,true)['geojson'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  return response()->streamDownload(fn()=>print($json),'site-'.$p->slug.'.geojson',['Content-Type'=>'application/geo+json']);
 }
}
