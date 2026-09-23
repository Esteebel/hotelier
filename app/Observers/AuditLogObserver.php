<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    /**
     * The model types to track.
     *
     * @var array<class-string<Model>>
     */
    protected array $tracked = [
        \App\Models\Guest::class,
        \App\Models\Reservation::class,
        \App\Models\Room::class,
        \App\Models\Payment::class,
        \App\Models\Invoice::class,
        \App\Models\Expense::class,
        \App\Models\Staff::class,
    ];

    public static function registerModels(): void
    {
        $observer = new self();
        foreach ($observer->tracked as $modelClass) {
            $modelClass::observe(self::class);
        }
    }

    public function created(Model $model): void
    {
        $this->log($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $original = $model->getOriginal();
        $changes = $model->getChanges();

        // Only log non-timestamp changes
        $filtered = array_diff_key($changes, array_flip(['updated_at']));

        if (! empty($filtered)) {
            $old = array_intersect_key($original, $filtered);
            $this->log($model, 'updated', $old, $filtered);
        }
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getOriginal(), []);
    }

    private function log(Model $model, string $event, array $old, array $new): void
    {
        if (! in_array(get_class($model), $this->tracked)) {
            return;
        }

        AuditLog::create([
            'branch_id' => $model->getAttribute('branch_id') ?? null,
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
