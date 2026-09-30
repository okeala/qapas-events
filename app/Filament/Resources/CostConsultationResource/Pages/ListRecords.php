<?php
namespace App\Filament\Resources\CostConsultationResource\Pages;
class ListRecords extends \Filament\Resources\Pages\ListRecords {
 protected static string $resource=\App\Filament\Resources\CostConsultationResource::class;
 protected function getHeaderActions(): array {return [\Filament\Actions\Action::make('prepare')->label('Préparer les courriers des coûts actuels')->action(function(){abort_unless(auth('admin')->user()?->is_active,403);app(\App\Domain\Procurement\Consultations::class)->synchronize();\Filament\Notifications\Notification::make()->title('Brouillons préparés ; textes déjà personnalisés conservés')->success()->send();})];}
}
