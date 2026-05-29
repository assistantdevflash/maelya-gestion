<?php

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Logge automatiquement les événements created/updated/deleted Eloquent.
 * Le modèle peut surcharger :
 *   - $auditExclude (array)  : colonnes à ne pas tracer (par défaut : timestamps, password)
 *   - auditLabel() : string  : libellé lisible
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLog::record('created', $model, method_exists($model, 'auditLabel') ? $model->auditLabel() : null, [
                'new' => $model->only($model->auditableAttributes()),
            ]);
        });

        static::updated(function ($model) {
            $excl = $model->auditExcluded();
            $changes = collect($model->getChanges())->except($excl)->all();
            if (empty($changes)) return;
            $old = collect($model->getOriginal())->only(array_keys($changes))->all();
            AuditLog::record('updated', $model, method_exists($model, 'auditLabel') ? $model->auditLabel() : null, [
                'old' => $old,
                'new' => $changes,
            ]);
        });

        static::deleted(function ($model) {
            AuditLog::record('deleted', $model, method_exists($model, 'auditLabel') ? $model->auditLabel() : null);
        });
    }

    protected function auditExcluded(): array
    {
        $base = ['updated_at', 'created_at', 'password', 'remember_token'];
        return array_merge($base, property_exists($this, 'auditExclude') ? $this->auditExclude : []);
    }

    protected function auditableAttributes(): array
    {
        return array_diff(array_keys($this->getAttributes()), $this->auditExcluded());
    }
}
