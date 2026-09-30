<?php
namespace App\Domain\Planning;
use Illuminate\Validation\ValidationException;
final class GeoGeometry {
 private static function fail(): never {throw ValidationException::withMessages(['geometry'=>'Géométrie WGS84 invalide : points, lignes ou polygones simples, 500 sommets par anneau maximum.']);}
 public static function validate(mixed $g): void {
  if(!is_array($g)||!isset($g['type'],$g['coordinates']))self::fail();$c=$g['coordinates'];
  switch($g['type']){
   case 'Point':self::point($c);break;
   case 'LineString':self::line($c);break;
   case 'Polygon':self::polygon($c);break;
   case 'MultiPolygon':if(!is_array($c)||count($c)<1||count($c)>30)self::fail();foreach($c as $polygon)self::polygon($polygon);break;
   default:self::fail();
  }
 }
 private static function point(mixed $p): void {if(!is_array($p)||!array_is_list($p)||count($p)!==2||!is_numeric($p[0])||!is_numeric($p[1])||!is_finite((float)$p[0])||!is_finite((float)$p[1])||abs($p[0])>180||abs($p[1])>85)self::fail();}
 private static function line(mixed $p): void {if(!is_array($p)||!array_is_list($p)||count($p)<2||count($p)>500)self::fail();foreach($p as $v)self::point($v);}
 private static function polygon(mixed $rings): void {
  if(!is_array($rings)||!array_is_list($rings)||count($rings)<1||count($rings)>10)self::fail();
  foreach($rings as $ring){self::line($ring);if(count($ring)<4||$ring[0]!=end($ring))self::fail();
   $open=array_slice($ring,0,-1);$xs=array_column($open,0);$ys=array_column($open,1);$dx=max($xs)-min($xs);$dy=max($ys)-min($ys);if($dx<=0||$dy<=0)self::fail();
   $scaled=array_map(fn($v)=>[($v[0]-min($xs))/$dx*100,($v[1]-min($ys))/$dy*100],$open);
   try{TerraceGeometry::validate($scaled,500);}catch(ValidationException){self::fail();}
  }
  $outer=['type'=>'Polygon','coordinates'=>[$rings[0]]];
  for($i=1;$i<count($rings);$i++){$hole=['type'=>'Polygon','coordinates'=>[$rings[$i]]];if(!GeoArea::coveredBy($hole,[$outer]))self::fail();for($j=1;$j<$i;$j++)if(GeoArea::overlaps($hole,['type'=>'Polygon','coordinates'=>[$rings[$j]]]))self::fail();}
 }
}
