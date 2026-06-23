<?php

namespace App\Models\Concerns;

use App\Support\InventoryGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait PerformsAtomicInventory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function create(array $attributes = []): Model
    {
        if (DB::transactionLevel() > 0) {
            /** @var Model $model */
            $model = parent::create($attributes);

            return $model;
        }

        return InventoryGuard::transaction(function () use ($attributes) {
            /** @var Model $model */
            $model = parent::create($attributes);

            return $model;
        });
    }

    public function delete(): ?bool
    {
        if (DB::transactionLevel() > 0) {
            return parent::delete();
        }

        return InventoryGuard::transaction(function (): ?bool {
            return parent::delete();
        });
    }

    public function save(array $options = []): bool
    {
        if (! $this->shouldSaveInventoryAtomically()) {
            return parent::save($options);
        }

        if (DB::transactionLevel() > 0) {
            return parent::save($options);
        }

        return InventoryGuard::transaction(function () use ($options): bool {
            return parent::save($options);
        });
    }

    protected function shouldSaveInventoryAtomically(): bool
    {
        return ! $this->exists;
    }
}
