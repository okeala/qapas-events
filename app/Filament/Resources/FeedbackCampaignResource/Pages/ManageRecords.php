<?php
namespace App\Filament\Resources\FeedbackCampaignResource\Pages;
use Filament\Actions\{CreateAction,Action};
class ManageRecords extends \Filament\Resources\Pages\ManageRecords {
 protected static string $resource=\App\Filament\Resources\FeedbackCampaignResource::class;
 protected function getHeaderActions(): array {return [CreateAction::make()->modalWidth('7xl')];}
}
