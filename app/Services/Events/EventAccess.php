<?php

namespace App\Services\Events;

use App\Models\PlannerEvent;
use Filament\Facades\Filament;

class EventAccess
{
    public static function canManage(): bool
    {
        $admin = auth('admin')->user();
        return $admin !== null && $admin->is_active === true
            && $admin->canAccessPanel(Filament::getPanel('admin'));
    }

    public static function authorize(): void
    {
        abort_unless(self::canManage(), 403);
    }

    public static function authorizePublic(PlannerEvent $event): void
    {
        if (! $event->is_demo) { return; }
        abort_unless(app()->environment('local', 'testing') && config('event_planner.demo_enabled')
            && in_array(request()->getHost(), ['localhost', '127.0.0.1', '[::1]', '::1'], true), 404);
    }
}
