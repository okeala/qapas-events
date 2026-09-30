<?php
namespace App\Domain\Planning;
use Illuminate\Validation\ValidationException;
final class TerraceGeometry {
 public static function validate(mixed $points): void {
  $fail=fn()=>throw ValidationException::withMessages(['boundary'=>'Dessinez un polygone simple de 3 à 80 sommets dans le plan.']);
  if(!is_array($points)||!array_is_list($points)||count($points)<3||count($points)>80) $fail();
  foreach($points as $p) if(!is_array($p)||count($p)!==2||!array_is_list($p)||!is_numeric($p[0])||!is_numeric($p[1])||!is_finite((float)$p[0])||!is_finite((float)$p[1])||min($p)<0||max($p)>100) $fail();
  $area=0;$n=count($points);
  for($i=0;$i<$n;$i++) {
   $a=$points[$i];$b=$points[($i+1)%$n];if($a==$b) $fail();$area+=$a[0]*$b[1]-$b[0]*$a[1];
   for($j=$i+1;$j<$n;$j++) {
    if($j===$i+1||($i===0&&$j===$n-1)) continue;
    if(self::intersects($a,$b,$points[$j],$points[($j+1)%$n])) $fail();
   }
  }
  if(abs($area)<0.0001) $fail();
 }
 private static function cross(array $a,array $b,array $c): float {return ($b[0]-$a[0])*($c[1]-$a[1])-($b[1]-$a[1])*($c[0]-$a[0]);}
 private static function onSegment(array $p,array $a,array $b): bool {return abs(self::cross($a,$b,$p))<0.000001 && $p[0]>=min($a[0],$b[0])-0.000001 && $p[0]<=max($a[0],$b[0])+0.000001 && $p[1]>=min($a[1],$b[1])-0.000001 && $p[1]<=max($a[1],$b[1])+0.000001;}
 private static function intersects(array $a,array $b,array $c,array $d): bool {
  return (self::cross($a,$b,$c)*self::cross($a,$b,$d)<0 && self::cross($c,$d,$a)*self::cross($c,$d,$b)<0) || self::onSegment($a,$c,$d)||self::onSegment($b,$c,$d)||self::onSegment($c,$a,$b)||self::onSegment($d,$a,$b);
 }
 public static function contains(array $polygon,float $x,float $y): bool {
  $inside=false;$n=count($polygon);
  for($i=0,$j=$n-1;$i<$n;$j=$i++) {
   $a=$polygon[$j];$b=$polygon[$i];if(self::onSegment([$x,$y],$a,$b)) return true;
   if((($a[1]>$y)!==($b[1]>$y)) && $x<($b[0]-$a[0])*($y-$a[1])/($b[1]-$a[1])+$a[0]) $inside=!$inside;
  }
  return $inside;
 }
}
