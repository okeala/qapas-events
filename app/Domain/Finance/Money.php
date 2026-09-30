<?php
namespace App\Domain\Finance;
use InvalidArgumentException;
final class Money {
 public static function net(int $gross, int $vatBasisPoints): int {
  if ($gross<0 || $vatBasisPoints<0 || $vatBasisPoints>10000) throw new InvalidArgumentException('Invalid amount or VAT rate');
  return intdiv($gross*10000+intdiv(10000+$vatBasisPoints,2),10000+$vatBasisPoints);
 }
 public static function format(int $cents): string {return number_format($cents/100,2,',',' ').' €';}
}
