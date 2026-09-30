<?php
namespace App\Domain\Finance;
use App\Models\Scenario;
final class OrganizerCost {
 public static function amount(Scenario $s): int {return max(($s->organizer_full_monthly_cents??0)*$s->months,$s->minimum_organizer_charges_cents??0);}
}
