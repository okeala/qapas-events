<?php
namespace App\Filament\Pages;
use App\Models\{EventProject,Terrace,Activity};
use App\Domain\Planning\SitePlan as Plan;
use Illuminate\Support\Facades\{DB,Gate,Validator};
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
class SitePlan extends \Filament\Pages\Page {
 use WithFileUploads;
 protected static ?string $title='Plan des terrasses';
 protected static ?string $navigationLabel='Plan des terrasses';
 protected static string|\UnitEnum|null $navigationGroup='4 · Préparer';
 protected static ?int $navigationSort=10;
 protected string $view='filament.pages.site-plan';
 public ?string $projectId=null;
 public $planUpload=null;
 public int $revision=0;
 public static function canAccess(): bool {return auth('admin')->user()?->is_active===true;}
 public function mount(): void {$this->projectId=EventProject::orderBy('id')->value('public_id');}
 public function project(): ?EventProject {return $this->projectId?EventProject::where('public_id',$this->projectId)->first():null;}
 private function authorizedProject(): EventProject {
  abort_unless(self::canAccess(),403);$p=$this->project();abort_unless($p,404);Gate::forUser(auth('admin')->user())->authorize('update',$p);return $p;
 }
 public function uploadPlan(): void {
  $p=$this->authorizedProject();$this->validate(['planUpload'=>'required|file|mimes:png,jpg,jpeg,webp|max:8192']);
  if($p->terraces()->exists()) throw ValidationException::withMessages(['planUpload'=>'Le plan possède des terrasses. Utilisez une nouvelle édition pour un autre plan afin de conserver les placements existants.']);
  $path=app(Plan::class)->upload($this->planUpload);$p->update(['plan_image'=>$path,'plan_is_public'=>false]);$this->planUpload=null;$this->revision++;
 }
 public function saveTerrace(string $name,array $points,?string $id=null): void {
  $p=$this->authorizedProject();abort_unless(app(Plan::class)->dimensions($p),422);
  Validator::make(['name'=>$name],['name'=>'required|string|max:120'])->validate();
  $t=$id?$p->terraces()->where('public_id',$id)->firstOrFail():new Terrace(['event_project_id'=>$p->id]);
  $t->fill(['name'=>$name,'boundary'=>$points]);
  DB::transaction(function()use($t){$t->save();foreach($t->activities as $a){$a->risk_reviewed=false;if($a->status==='approved')$a->status='testing';$a->save();}});
  $this->revision++;$this->dispatch('plan-saved');
 }
 public function placeActivity(string $activityId,string $terraceId,float $x,float $y): void {
  $p=$this->authorizedProject();$a=$p->activities()->where('public_id',$activityId)->firstOrFail();$t=$p->terraces()->where('public_id',$terraceId)->firstOrFail();
  $a->fill(['terrace_id'=>$t->id,'map_x'=>$x,'map_y'=>$y,'risk_reviewed'=>false]);if($a->status==='approved')$a->status='testing';$a->save();$this->revision++;$this->dispatch('plan-saved');
 }
}
