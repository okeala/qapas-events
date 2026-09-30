<?php
namespace Tests\Unit;
use App\Domain\Finance\Money;
use PHPUnit\Framework\TestCase;
class MoneyTest extends TestCase {
 public function test_ttc_is_not_mistaken_for_turnover(): void {
  $this->assertSame(40650,Money::net(50000,2300));
  $this->assertSame(50000,Money::net(50000,0));
  $this->assertSame(4064,Money::net(4999,2300));
 }
}
