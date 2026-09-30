<?php
namespace App\Domain\Planning;
use Illuminate\Validation\ValidationException;
/** Planar tests for small site footprints; boundaries may touch. Coordinates are WGS84. */
final class GeoArea {
 private const EPS=1e-10;
 private static function polygons(array $g): array {return $g['type']==='Polygon'?[$g['coordinates']]:($g['type']==='MultiPolygon'?$g['coordinates']:[]);}
 private static function segments(array $polygons): array {$s=[];foreach($polygons as $p)foreach($p as $ring)for($i=1;$i<count($ring);$i++)$s[]=[$ring[$i-1],$ring[$i]];return $s;}
 private static function intervals(array $polygons,float $x): array {
  $all=[];foreach($polygons as $p){$ys=[];foreach(self::segments([$p]) as [$a,$b])if(($a[0]<$x&&$b[0]>$x)||($b[0]<$x&&$a[0]>$x))$ys[]=$a[1]+($x-$a[0])*($b[1]-$a[1])/($b[0]-$a[0]);sort($ys,SORT_NUMERIC);for($i=0;$i+1<count($ys);$i+=2)$all[]=[$ys[$i],$ys[$i+1]];}
  usort($all,fn($a,$b)=>$a[0]<=>$b[0]);$merged=[];foreach($all as $range){$i=count($merged)-1;if($i>=0&&$range[0]<=$merged[$i][1]+self::EPS)$merged[$i][1]=max($merged[$i][1],$range[1]);else $merged[]=$range;}return $merged;
 }
 private static function slices(array $a,array $b): array {
  $s=self::segments(array_merge($a,$b));if(count($s)>1200)throw ValidationException::withMessages(['geometry'=>'Simplifier les contours : au maximum 1 200 segments pour cette vérification.']);$xs=[];
  foreach($s as [$p,$q]){$xs[]=(float)$p[0];$xs[]=(float)$q[0];}
  for($i=0;$i<count($s);$i++)for($j=$i+1;$j<count($s);$j++){
   [$p,$q]=$s[$i];[$r,$t]=$s[$j];$dx=$q[0]-$p[0];$dy=$q[1]-$p[1];$ex=$t[0]-$r[0];$ey=$t[1]-$r[1];$den=$dx*$ey-$dy*$ex;if(abs($den)<1e-20)continue;
   $u=(($r[0]-$p[0])*$ey-($r[1]-$p[1])*$ex)/$den;$v=(($r[0]-$p[0])*$dy-($r[1]-$p[1])*$dx)/$den;if($u>0&&$u<1&&$v>0&&$v<1)$xs[]=$p[0]+$u*$dx;
  }
  sort($xs,SORT_NUMERIC);$mid=[];for($i=1;$i<count($xs);$i++)if($xs[$i]-$xs[$i-1]>self::EPS)$mid[]=($xs[$i]+$xs[$i-1])/2;return $mid;
 }
 public static function coveredBy(array $subject,array $containers): bool {
  $a=self::polygons($subject);$b=[];foreach($containers as $g)$b=array_merge($b,self::polygons($g));if(!$a||!$b)return false;
  foreach(self::slices($a,$b) as $x){$cover=self::intervals($b,$x);foreach(self::intervals($a,$x) as [$low,$high]){if($high-$low<=self::EPS)continue;$ok=false;foreach($cover as [$l,$h])if($l<=$low+self::EPS&&$h>=$high-self::EPS){$ok=true;break;}if(!$ok)return false;}}return true;
 }
 public static function overlaps(array $one,array $two): bool {
  $a=self::polygons($one);$b=self::polygons($two);foreach(self::slices($a,$b) as $x)foreach(self::intervals($a,$x) as [$l,$h])foreach(self::intervals($b,$x) as [$m,$n])if(min($h,$n)-max($l,$m)>self::EPS)return true;return false;
 }
}
