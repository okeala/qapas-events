<?php
namespace App\Filament\Resources\FreguesiaInvitationResource\Pages;
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {protected static string $resource=\App\Filament\Resources\FreguesiaInvitationResource::class;protected function getHeaderActions(): array {return [\Filament\Actions\CreateAction::make()];}}
