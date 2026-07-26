<?php

namespace App\Models\Concerns;

use RuntimeException;

/**
 * Phase 14R.6 — makes a model append-only at the MODEL layer.
 *
 * The schema already refuses to carry `updated_at` or a soft-delete column, but
 * schema alone would not stop application code calling `->update()` or
 * `->delete()`. This concern makes those attempts fail closed and loudly, so a
 * medico-legal record cannot be quietly rewritten by a future refactor.
 *
 * Inserts are allowed exactly once; every subsequent write is rejected.
 */
trait ImmutableClinicalSnapshot
{
    public static function bootImmutableClinicalSnapshot(): void
    {
        static::updating(function ($model) {
            throw static::immutableViolation('update', $model);
        });

        static::deleting(function ($model) {
            throw static::immutableViolation('delete', $model);
        });

        static::saving(function ($model) {
            // `saving` also fires on insert; only an existing row is a rewrite.
            if ($model->exists && $model->isDirty()) {
                throw static::immutableViolation('save', $model);
            }
        });
    }

    /**
     * Guard the low-level escape hatches too. `forceFill()->save()` and
     * `->update()` both route through here.
     */
    public function update(array $attributes = [], array $options = [])
    {
        if ($this->exists) {
            throw static::immutableViolation('update', $this);
        }

        return parent::update($attributes, $options);
    }

    public function delete()
    {
        throw static::immutableViolation('delete', $this);
    }

    public function forceDelete()
    {
        throw static::immutableViolation('force delete', $this);
    }

    private static function immutableViolation(string $operation, $model): RuntimeException
    {
        return new RuntimeException(sprintf(
            '%s is an immutable clinical snapshot; %s is not permitted (id: %s).',
            class_basename($model),
            $operation,
            $model->getKey() ?? 'new'
        ));
    }
}
