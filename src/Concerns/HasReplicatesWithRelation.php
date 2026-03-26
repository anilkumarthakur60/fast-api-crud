<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * @phpstan-require-extends Model
 */
trait HasReplicatesWithRelation
{
    /**
     * Replicate this model along with all of its loaded relations.
     *
     * @return static The newly replicated model instance.
     *
     * @throws Exception
     */
    public function replicateWithRelations(): static
    {
        $newModel = $this->replicate();

        foreach ($this->matchingCastableAttributes() as $attribute => $casts) {
            $newModel->{$attribute} = $this->castAttribute($attribute, $this->{$attribute});
        }

        $newModel->save();

        foreach ($this->getRelations() as $relationName => $relationValue) {
            if (!is_string($relationName) || !$relationValue) {
                continue;
            }

            if (!method_exists($this, $relationName)) {
                continue;
            }

            $relationInstance = $this->{$relationName}();

            if (!$relationInstance instanceof Relation) {
                continue;
            }

            match (true) {
                $relationInstance instanceof MorphTo => $this->replicateBelongsTo($newModel, $relationName, $relationValue),
                $relationInstance instanceof BelongsTo => $this->replicateBelongsTo($newModel, $relationName, $relationValue),

                $relationInstance instanceof MorphOne => $this->replicateHasOne($newModel, $relationName, $relationValue),
                $relationInstance instanceof HasOne => $this->replicateHasOne($newModel, $relationName, $relationValue),

                $relationInstance instanceof MorphMany => $this->replicateHasMany($newModel, $relationName, $relationValue),
                $relationInstance instanceof HasMany => $this->replicateHasMany($newModel, $relationName, $relationValue),

                $relationInstance instanceof MorphToMany => $this->replicateBelongsToMany($newModel, $relationName, $relationValue, $relationInstance),
                $relationInstance instanceof BelongsToMany => $this->replicateBelongsToMany($newModel, $relationName, $relationValue, $relationInstance),

                $relationInstance instanceof HasOneThrough => throw new Exception("HasOneThrough relationship '{$relationName}' is not supported for replication."),
                $relationInstance instanceof HasManyThrough => throw new Exception("HasManyThrough relationship '{$relationName}' is not supported for replication."),

                default => throw new Exception("Relation '{$relationName}' of type '" . get_class($relationInstance) . "' is not supported for replication."),
            };
        }

        return $newModel;
    }

    /**
     * Return castable attributes that need re-applying during replication.
     *
     * @return array<string, string>
     */
    public function matchingCastableAttributes(): array
    {
        /** @var array<string, string> $matched */
        $matched = [];

        foreach ($this->getCasts() as $attribute => $castType) {
            if (!is_string($attribute) || !is_string($castType)) {
                continue;
            }

            $normalizedType = $this->normalizeCastType(strtolower(trim($castType)));

            if (
                isset($this->{$attribute})
                && is_scalar($this->{$attribute})
                && $this->isCastable($this->{$attribute}, $normalizedType)
            ) {
                $matched[$attribute] = $normalizedType;
            }
        }

        return $matched;
    }

    /**
     * Normalize cast type to a category.
     */
    private function normalizeCastType(string $castType): string
    {
        if (in_array($castType, ['int', 'integer', 'real', 'float', 'double', 'decimal'], true)) {
            return 'numeric';
        }

        if (in_array($castType, ['json', 'array', 'object', 'collection'], true)) {
            return 'json';
        }

        return $castType;
    }

    /**
     * Determine if a given value matches a particular cast type.
     */
    private function isCastable(mixed $value, string $type): bool
    {
        return match ($type) {
            'numeric' => is_numeric($value),
            'bool', 'boolean' => is_bool($value) || (is_string($value) && in_array(strtolower($value), ['1', 'true', 'yes'], true)),
            'string' => is_string($value),
            'json' => is_array($value) || (is_object($value) && method_exists($value, 'toArray')),
            default => false,
        };
    }

    /**
     * Replicate a BelongsTo/MorphTo relationship.
     */
    private function replicateRelatedModel(Model $relatedModel): Model
    {
        if (method_exists($relatedModel, 'replicateWithRelations')) {
            $result = $relatedModel->replicateWithRelations();

            if ($result instanceof Model) {
                return $result;
            }
        }

        return $relatedModel->replicate();
    }

    /**
     * Replicate a BelongsTo/MorphTo relationship.
     */
    private function replicateBelongsTo(Model $newModel, string $relationName, mixed $relationValue): void
    {
        if (!$relationValue instanceof Model) {
            return;
        }

        $replicatedParent = $this->replicateRelatedModel($relationValue);
        $relation = $newModel->{$relationName}();

        if ($relation instanceof BelongsTo) {
            $relation->associate($replicatedParent);
        }

        $newModel->save();
    }

    /**
     * Replicate a HasOne/MorphOne relationship.
     */
    private function replicateHasOne(Model $newModel, string $relationName, mixed $relationValue): void
    {
        if (!$relationValue instanceof Model) {
            return;
        }

        $newRelated = $this->replicateRelatedModel($relationValue);
        $relation = $newModel->{$relationName}();

        if ($relation instanceof HasOne || $relation instanceof MorphOne) {
            $relation->save($newRelated);
        }
    }

    /**
     * Replicate a HasMany/MorphMany relationship.
     */
    private function replicateHasMany(Model $newModel, string $relationName, mixed $relationValue): void
    {
        if (!$relationValue instanceof Collection) {
            return;
        }

        /** @var Collection<int, Model> $relatedCollection */
        $relatedCollection = $relationValue;
        foreach ($relatedCollection as $childModel) {
            $newChild = $this->replicateRelatedModel($childModel);
            $relation = $newModel->{$relationName}();

            if ($relation instanceof HasMany || $relation instanceof MorphMany) {
                $relation->save($newChild);
            }
        }
    }

    /**
     * Replicate a BelongsToMany/MorphToMany relationship.
     *
     * @param  BelongsToMany<Model, Model>|MorphToMany<Model, Model>  $relationInstance
     */
    private function replicateBelongsToMany(Model $newModel, string $relationName, mixed $relationValue, BelongsToMany|MorphToMany $relationInstance): void
    {
        if (!$relationValue instanceof Collection) {
            return;
        }

        /** @var Collection<int, Model> $relatedCollection */
        $relatedCollection = $relationValue;
        $ids = $relatedCollection->pluck(
            $relationInstance->getRelated()->getKeyName()
        )->toArray();
        $relation = $newModel->{$relationName}();

        if ($relation instanceof BelongsToMany) {
            $relation->sync($ids);
        }
    }
}
