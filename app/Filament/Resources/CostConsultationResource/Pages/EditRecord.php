<?php
namespace App\Filament\Resources\CostConsultationResource\Pages;
class EditRecord extends \Filament\Resources\Pages\EditRecord {
 protected static string $resource=\App\Filament\Resources\CostConsultationResource::class;
 protected function getHeaderActions(): array {return array_map(fn($locale)=>\Filament\Actions\Action::make('download_'.$locale)->label('Télécharger email '.strtoupper($locale))->url(fn()=>route('consultation.email',['consultation'=>$this->getRecord(),'locale'=>$locale])),['pt','fr']);}
}
