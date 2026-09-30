<?php
namespace App\Domain\Planning;
use Illuminate\Validation\ValidationException;
final class GeoImport {
 private function fail(string $m): never {throw ValidationException::withMessages(['importFile'=>$m]);}
 public function parse(string $text,string $format): array {
  if(strlen($text)>2097152)$this->fail('Import limité à 2 Mo.');
  if($format==='kml')return $this->kml($text);
  try{$data=json_decode($text,true,32,JSON_THROW_ON_ERROR);}catch(\JsonException){$this->fail('GeoJSON non lisible.');}
  if(isset($data['crs']))$this->fail('Exporter en GeoJSON WGS84 (longitude, latitude), sans ancien champ crs.');
  $features=($data['type']??null)==='FeatureCollection'?($data['features']??[]):(($data['type']??null)==='Feature'?[$data]:[]);
  if(!is_array($features)||count($features)<1||count($features)>100)$this->fail('Importer de 1 à 100 objets GeoJSON.');
  $result=[];foreach($features as $i=>$f){$geometry=$f['geometry']??null;GeoGeometry::validate($geometry);$name=$f['properties']['name']??'Élément '.($i+1);$result[]=['name'=>mb_substr(is_string($name)?strip_tags($name):'Élément '.($i+1),0,120),'geometry'=>$geometry];}return $result;
 }
 private function kml(string $text): array {
  if(preg_match('/<!DOCTYPE|<!ENTITY/i',$text))$this->fail('DTD et entités XML interdites.');
  $doc=new \DOMDocument();$old=libxml_use_internal_errors(true);try{$ok=$doc->loadXML($text,LIBXML_NONET);}finally{libxml_clear_errors();libxml_use_internal_errors($old);}
  if(!$ok)$this->fail('KML non lisible.');$xp=new \DOMXPath($doc);
  if($xp->query('//*[local-name()="NetworkLink" or local-name()="Model" or local-name()="GroundOverlay"]')->length)$this->fail('Les liens externes, modèles et images KML ne sont pas importés. Exporter les géométries.');
  $places=$xp->query('//*[local-name()="Placemark"]');if($places->length<1||$places->length>100)$this->fail('Importer de 1 à 100 objets KML.');
  $result=[];foreach($places as $i=>$place){
   $name=trim($xp->evaluate('string(./*[local-name()="name"])',$place))?:'Élément '.($i+1);
   $polygons=$xp->query('.//*[local-name()="Polygon"]',$place);$lines=$xp->query('.//*[local-name()="LineString"]',$place);$points=$xp->query('.//*[local-name()="Point"]',$place);
   if($polygons->length){if($lines->length||$points->length)$this->fail('Séparer les géométries mixtes en objets KML distincts.');$multi=[];foreach($polygons as $poly){$rings=[];$outer=$xp->query('./*[local-name()="outerBoundaryIs"]//*[local-name()="coordinates"]',$poly);if($outer->length!==1)$this->fail('Contour KML absent.');$rings[]=$this->coordinates($outer->item(0)->textContent);foreach($xp->query('./*[local-name()="innerBoundaryIs"]//*[local-name()="coordinates"]',$poly) as $inner)$rings[]=$this->coordinates($inner->textContent);$multi[]=$rings;}$g=count($multi)===1?['type'=>'Polygon','coordinates'=>$multi[0]]:['type'=>'MultiPolygon','coordinates'=>$multi];}
   elseif($lines->length===1&&!$points->length)$g=['type'=>'LineString','coordinates'=>$this->coordinates($xp->evaluate('string(.//*[local-name()="coordinates"])',$lines->item(0)))];
   elseif($points->length===1&&!$lines->length){$c=$this->coordinates($xp->evaluate('string(.//*[local-name()="coordinates"])',$points->item(0)));if(count($c)!==1)$this->fail('Point KML invalide.');$g=['type'=>'Point','coordinates'=>$c[0]];}
   else $this->fail('Géométrie KML non prise en charge.');
   GeoGeometry::validate($g);$result[]=['name'=>mb_substr(strip_tags($name),0,120),'geometry'=>$g];
  }return $result;
 }
 private function coordinates(string $s): array {$result=[];foreach(preg_split('/\s+/',trim($s),-1,PREG_SPLIT_NO_EMPTY) as $tuple){$v=explode(',',$tuple);if(count($v)<2||!is_numeric($v[0])||!is_numeric($v[1]))$this->fail('Coordonnées KML invalides.');$result[]=[(float)$v[0],(float)$v[1]];}return $result;}
}
