<?php
namespace App\Domain\Finance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
final class Pricing {
 public const STATUSES=['manual'=>'Saisie manuelle existante','estimate'=>'Hypothèse de travail','reference'=>'Prix public de référence','quoted'=>'Devis reçu, à valider','confirmed'=>'Prix et périmètre validés'];
 public static function pending(Model $m): bool {return in_array($m->pricing_status,['estimate','reference','quoted'],true);}
 public static function validate(Model $m): void {
  if(!array_key_exists($m->pricing_status??'manual',self::STATUSES))throw ValidationException::withMessages(['pricing_status'=>'État de chiffrage inconnu.']);
  if($m->exists&&$m->getOriginal('pricing_status')!=='manual'&&$m->pricing_status==='manual')throw ValidationException::withMessages(['pricing_status'=>'Valider le prix avec sa source ; le statut historique est réservé aux anciennes lignes.']);
  if($m->exists&&$m->getOriginal('pricing_status')==='confirmed'&&$m->isDirty(['unit_gross_cents','vat_basis_points','deductible','forecast_quantity','quantity','basis','unit','price_source','shared_cost_key']))$m->pricing_status='estimate';
  if($m->pricing_status==='confirmed'&&($m->unit_gross_cents===null||$m->vat_basis_points===null||blank($m->price_source)||!$m->price_checked_at||\Illuminate\Support\Carbon::parse($m->price_checked_at)->isFuture()))throw ValidationException::withMessages(['pricing_status'=>'Enregistrer montant, IVA, source et date passée avant validation.']);
 }
 public static function fields(): array {return [
  \Filament\Forms\Components\Select::make('pricing_status')->label('Fiabilité du chiffrage')->options(fn(?Model $record)=>$record?->pricing_status==='manual'?self::STATUSES:array_diff_key(self::STATUSES,['manual'=>true]))->default('estimate')->required()->helperText('Une hypothèse, un tarif public ou un devis non validé ne débloque pas le lancement.'),
  \Filament\Forms\Components\DatePicker::make('price_checked_at')->label('Date de vérification du prix')->maxDate(today()),
  \Filament\Forms\Components\Textarea::make('price_source')->label('Source, hypothèses, exclusions et conditions du prix')->maxLength(10000)->columnSpanFull(),
 ];}
 public static function forecast(Model $m,int $quantity): ?int {
  if($m->unit_gross_cents===null)return null;
  if(($m->kind??'cost')==='revenue')return $m->vat_basis_points===null?null:Money::net($m->unit_gross_cents,$m->vat_basis_points)*$quantity;
  return ($m->deductible&&$m->vat_basis_points!==null?Money::net($m->unit_gross_cents,$m->vat_basis_points):$m->unit_gross_cents)*$quantity;
 }
}
