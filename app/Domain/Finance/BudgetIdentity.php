<?php
namespace App\Domain\Finance;
use App\Models\BudgetLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class BudgetIdentity {
 public static function key(array $data): string {$name=trim(preg_replace('/[^a-z0-9]+/',' ',strtolower(Str::ascii($data['name']??''))));return hash('sha256',implode('|',[$data['scenario_id']??'',$data['kind']??'',$data['scope']??'common',$data['stand_id']??0,$data['stand_partner_id']??0,$name]));}
 public static function duplicates(BudgetLine $line){return BudgetLine::where('scenario_id',$line->scenario_id)->whereNull('superseded_by_id')->where('id','!=',$line->id)->get()->filter(fn($other)=>self::key($other->getAttributes())===self::key($line->getAttributes()));}
 public function supersede(BudgetLine $duplicate,int $canonicalId): void {
  abort_unless(auth('admin')->user()?->is_active,403);
  DB::transaction(function()use($duplicate,$canonicalId){$rows=BudgetLine::whereIn('id',[$duplicate->id,$canonicalId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');$d=$rows->get($duplicate->id);$c=$rows->get($canonicalId);
   if(!$d||!$c||$d->id===$c->id||$c->superseded_by_id||self::key($d->getAttributes())!==self::key($c->getAttributes())||$d->committed_quantity||$d->paid_quantity||$d->reimbursed_cents||$d->receipt_reference)throw ValidationException::withMessages(['canonical'=>'Écarter uniquement une répétition du même poste et de la même unité sans engagement, règlement ni justificatif financier. Les montants ne sont pas additionnés.']);
   $d->update(['superseded_by_id'=>$c->id]);if(!self::duplicates($c)->count())$c->save();
  },3);
 }
}
