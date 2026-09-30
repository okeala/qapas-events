<?php
namespace App\Filament\Pages;
class CampaignPosters extends \Filament\Pages\Page {
 protected static string|\UnitEnum|null $navigationGroup='3 · Mobiliser';protected static ?int $navigationSort=52;protected static ?string $title='Trois affiches · prototypes de campagne';protected static ?string $navigationLabel='Visuels de campagne';protected string $view='filament.pages.campaign-posters';
 public static function canAccess(): bool {return (bool)auth('admin')->user()?->is_active;}
}
