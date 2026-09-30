<?php
namespace App\Filament\Resources\PublicationDispatchResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\PublicationDispatchResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\Action::make('prepare')->label('Vérifier les phases et traiter les diffusions autorisées')->requiresConfirmation()->action(fn()=>app(\App\Domain\Promotion\PromotionPipeline::class)->tick())];}}
