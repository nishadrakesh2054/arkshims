<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLog::record($model, 'created', null, $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            if ($model->wasChanged()) {
                AuditLog::record(
                    $model,
                    'updated',
                    $model->getOriginal(),
                    $model->getChanges(),
                );
            }
        });

        static::deleted(function (Model $model): void {
            AuditLog::record($model, 'deleted', $model->getAttributes(), null);
        });
    }
}
