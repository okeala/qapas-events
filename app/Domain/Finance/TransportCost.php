<?php
namespace App\Domain\Finance;
use Illuminate\Support\Facades\Validator;
final class TransportCost {
 // Fuel is expressed in millilitres/100 km; all money remains integer cents.
 public static function calculate(array $input): array {
  Validator::make($input,['round_trips'=>'required|integer|between:1,100','one_way_km'=>'required|integer|between:1,1000','one_way_minutes'=>'required|integer|between:1,1440','handling_minutes_per_trip'=>'required|integer|between:0,1440','fuel_ml_per_100km'=>'required|integer|between:1,100000','fuel_cents_per_litre'=>'required|integer|between:0,10000'])->validate();
  $km=2*$input['round_trips']*$input['one_way_km'];$ml=intdiv($km*$input['fuel_ml_per_100km']+50,100);
  return ['km'=>$km,'fuel_ml'=>$ml,'fuel_cents'=>intdiv($ml*$input['fuel_cents_per_litre']+500,1000),'minutes'=>$input['round_trips']*(2*$input['one_way_minutes']+$input['handling_minutes_per_trip'])];
 }
}
