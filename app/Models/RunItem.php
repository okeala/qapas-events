<?php
namespace App\Models;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\{Rule, ValidationException};

class RunItem extends Record
{
    public const STATUSES = ['planned' => 'Prévue', 'running' => 'En cours', 'done' => 'Terminée', 'cancelled' => 'Annulée'];
    protected $attributes = ['status' => 'planned'];

    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime']; }
    public function eventProject() { return $this->belongsTo(EventProject::class); }

    protected static function booted(): void
    {
        parent::booted();
        static::saving(function (self $item): void {
            Validator::make($item->getAttributes(), [
                'event_project_id' => 'required|integer|exists:event_projects,id',
                'name' => 'required|string|max:255',
                'owner' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'starts_at' => 'required|date',
                'ends_at' => 'required|date|after:starts_at',
                'status' => ['required', Rule::in(array_keys(self::STATUSES))],
                'notes' => 'nullable|string|max:10000',
            ])->validate();
            if ($item->exists && $item->isDirty('event_project_id')) {
                throw ValidationException::withMessages(['event_project_id' => 'Une séquence reste dans son édition.']);
            }
        });
    }
}
