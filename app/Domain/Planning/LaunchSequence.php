<?php
namespace App\Domain\Planning;
use App\Models\Idea;
use Illuminate\Validation\ValidationException;
final class LaunchSequence {
 public function validateOrder(array $order): void {
  abort_unless(auth('admin')->user()?->is_active,403);
  $ids=array_map('intval',$order);
  $fail=fn($message)=>throw ValidationException::withMessages(['sort_order'=>$message]);
  if(!$ids||count($ids)!==count(array_unique($ids)))$fail('Ordre vide ou doublons.');
  $items=Idea::whereIn('id',$ids)->get();
  if($items->count()!==count($ids)||$items->pluck('event_project_id')->unique()->count()!==1)$fail('Réordonner une seule édition à la fois.');
  if(Idea::where('event_project_id',$items->first()->event_project_id)->count()!==count($ids))$fail('Afficher toutes les étapes de cette édition, sans recherche ni filtre supplémentaire.');
  $positions=array_flip($ids);
  foreach($items as $item)foreach($item->depends_on??[] as $dependency){
   if(!isset($positions[$dependency])||$positions[$dependency]>=$positions[$item->id])$fail('Le préalable de « '.$item->name.' » doit rester avant cette étape. Modifiez explicitement la dépendance si la démarche change.');
  }
 }
}
