<?php

namespace App\Services\Consultation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationUserRelationLoader
{
    /** @var array<int, User|null> */
    private array $usersById = [];

    /**
     * @param  array<int, array{0: mixed, 1: array<int, string>}>  $groups
     */
    public function load(array $groups): void
    {
        $targets = [];
        $userIds = [];

        foreach ($groups as [$models, $relations]) {
            $modelCollection = $models instanceof Model
                ? collect([$models])
                : collect($models)->flatten();

            foreach ($modelCollection->filter(fn ($model) => $model instanceof Model) as $model) {
                foreach ($relations as $relationName) {
                    if ($model->relationLoaded($relationName) || ! method_exists($model, $relationName)) {
                        continue;
                    }

                    $relation = $model->{$relationName}();
                    if (! $relation instanceof BelongsTo || ! $relation->getRelated() instanceof User) {
                        continue;
                    }

                    $userId = $model->getAttribute($relation->getForeignKeyName());
                    $targets[] = [$model, $relationName, $userId ? (int) $userId : null];

                    if ($userId) {
                        $userIds[(int) $userId] = true;
                    }
                }
            }
        }

        $missingIds = array_values(array_diff(array_keys($userIds), array_keys($this->usersById)));
        if ($missingIds !== []) {
            $users = User::query()->whereKey($missingIds)->get()->keyBy('id');

            foreach ($missingIds as $userId) {
                $this->usersById[$userId] = $users->get($userId);
            }
        }

        foreach ($targets as [$model, $relationName, $userId]) {
            $model->setRelation($relationName, $userId ? ($this->usersById[$userId] ?? null) : null);
        }
    }
}
