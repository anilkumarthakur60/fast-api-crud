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
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use SplObjectStorage;

/**
 * Replicate a model along with its relations to any depth.
 *
 * Usage:
 *   $clone = $post->replicateWithRelations();
 *   $clone = $post->replicateWithRelations(['comments.replies', 'tags']);
 *   $clone = $post->replicateWithRelations(except: ['slug', 'published_at']);
 *
 * @phpstan-require-extends Model
 */
trait HasReplicatesWithRelation
{
    /**
     * Replicate this model along with specified or loaded relations.
     *
     * @param  array<int, string>  $relations  Relations to replicate (dot notation for depth). Empty = use loaded relations.
     * @param  array<int, string>  $except  Attributes to exclude from the replica.
     * @return static The newly saved replica.
     *
     * @throws Exception
     */
    public function replicateWithRelations(array $relations = [], array $except = []): static
    {
        /** @var SplObjectStorage<Model, Model> $visited */
        $visited = new SplObjectStorage;

        return $this->replicateWithRelationsUsing($relations, $except, $visited);
    }

    /**
     * Internal replication with circular reference tracking.
     *
     * @param  array<int, string>  $relations
     * @param  array<int, string>  $except
     * @param  SplObjectStorage<Model, Model>  $visited  Tracks original→clone to prevent infinite loops.
     *
     * @throws Exception
     */
    private function replicateWithRelationsUsing(array $relations, array $except, SplObjectStorage $visited): static
    {
        // Circular reference guard — return existing clone if we've seen this model
        if ($visited->contains($this)) {
            /** @var static */
            return $visited[$this];
        }

        // Eager-load requested relations if not already loaded
        if ($relations !== []) {
            $this->loadMissing($relations);
        }

        $newModel = $this->replicate($except !== [] ? $except : null);
        $this->reApplyCasts($newModel);
        $newModel->save();

        // Register in visited map before processing relations (handles circular refs)
        $visited[$this] = $newModel;

        foreach ($this->getRelations() as $relationName => $relationValue) {
            if (! is_string($relationName) || ! $relationValue) {
                continue;
            }

            if (! method_exists($this, $relationName)) {
                continue;
            }

            $relationInstance = $this->{$relationName}();

            if (! $relationInstance instanceof Relation) {
                continue;
            }

            match (true) {
                $relationInstance instanceof MorphTo,
                $relationInstance instanceof BelongsTo => $this->replicateBelongsTo($newModel, $relationName, $relationValue),

                $relationInstance instanceof MorphOne,
                $relationInstance instanceof HasOne => $this->replicateHasOne($newModel, $relationName, $relationValue, $visited),

                $relationInstance instanceof MorphMany,
                $relationInstance instanceof HasMany => $this->replicateHasMany($newModel, $relationName, $relationValue, $visited),

                $relationInstance instanceof MorphToMany,
                $relationInstance instanceof BelongsToMany => $this->replicateBelongsToMany($newModel, $relationName, $relationValue, $relationInstance),

                $relationInstance instanceof HasOneThrough,
                $relationInstance instanceof HasManyThrough => null, // Skip — "through" relations are derived, not owned

                default => null,
            };
        }

        return $newModel;
    }

    // -------------------------------------------------------------------------
    // Relation replicators
    // -------------------------------------------------------------------------

    /**
     * BelongsTo / MorphTo — associate with the SAME parent (don't duplicate it).
     */
    private function replicateBelongsTo(Model $newModel, string $relationName, mixed $relationValue): void
    {
        if (! $relationValue instanceof Model) {
            return;
        }

        $relation = $newModel->{$relationName}();

        if ($relation instanceof BelongsTo) {
            $relation->associate($relationValue);
            $newModel->save();
        }
    }

    /**
     * HasOne / MorphOne — deep-replicate the child and attach to new parent.
     *
     * @param  SplObjectStorage<Model, Model>  $visited
     */
    private function replicateHasOne(Model $newModel, string $relationName, mixed $relationValue, SplObjectStorage $visited): void
    {
        if (! $relationValue instanceof Model) {
            return;
        }

        $newChild = $this->deepReplicateChild($relationValue, $visited);
        $relation = $newModel->{$relationName}();

        if ($relation instanceof HasOne || $relation instanceof MorphOne) {
            $relation->save($newChild);
        }
    }

    /**
     * HasMany / MorphMany — deep-replicate each child and attach to new parent.
     *
     * @param  SplObjectStorage<Model, Model>  $visited
     */
    private function replicateHasMany(Model $newModel, string $relationName, mixed $relationValue, SplObjectStorage $visited): void
    {
        if (! $relationValue instanceof Collection) {
            return;
        }

        $relation = $newModel->{$relationName}();

        if (! ($relation instanceof HasMany || $relation instanceof MorphMany)) {
            return;
        }

        /** @var Collection<int, Model> $relatedCollection */
        $relatedCollection = $relationValue;

        foreach ($relatedCollection as $childModel) {
            $newChild = $this->deepReplicateChild($childModel, $visited);
            $relation->save($newChild);
        }
    }

    /**
     * BelongsToMany / MorphToMany — sync IDs with pivot data preserved.
     *
     * @param  BelongsToMany<Model, Model>|MorphToMany<Model, Model>  $relationInstance
     */
    private function replicateBelongsToMany(Model $newModel, string $relationName, mixed $relationValue, BelongsToMany|MorphToMany $relationInstance): void
    {
        if (! $relationValue instanceof Collection) {
            return;
        }

        $relation = $newModel->{$relationName}();

        if (! $relation instanceof BelongsToMany) {
            return;
        }

        /** @var Collection<int, Model> $relatedCollection */
        $relatedCollection = $relationValue;

        // Build sync array with pivot data preserved
        $pivotColumns = $relationInstance->getPivotColumns();
        /** @var array<int|string, array<string, mixed>> $syncData */
        $syncData = [];

        foreach ($relatedCollection as $relatedModel) {
            $key = $relatedModel->getKey();

            if (! is_int($key) && ! is_string($key)) {
                continue;
            }

            $pivot = $relatedModel->getRelation('pivot');

            if ($pivotColumns !== [] && $pivot instanceof Pivot) {
                $pivotData = [];
                foreach ($pivotColumns as $column) {
                    if (is_string($column)) {
                        $pivotData[$column] = $pivot->getAttribute($column);
                    }
                }
                $syncData[$key] = $pivotData;
            } else {
                $syncData[$key] = [];
            }
        }

        $relation->sync($syncData);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Deep-replicate a child model: if it uses this trait, replicate with its own relations;
     * otherwise do a simple replicate.
     *
     * @param  SplObjectStorage<Model, Model>  $visited
     */
    private function deepReplicateChild(Model $child, SplObjectStorage $visited): Model
    {
        // Already cloned (circular reference) — return the existing clone
        if ($visited->contains($child)) {
            /** @var Model */
            return $visited[$child];
        }

        if (method_exists($child, 'replicateWithRelationsUsing')) {
            $result = $child->replicateWithRelationsUsing([], [], $visited);

            if ($result instanceof Model) {
                return $result;
            }
        }

        // Simple replicate for models without the trait
        $newChild = $child->replicate();
        $newChild->save();
        $visited[$child] = $newChild;

        return $newChild;
    }

    /**
     * Re-apply castable attributes to the replicated model.
     */
    private function reApplyCasts(Model $newModel): void
    {
        foreach ($this->getCasts() as $attribute => $castType) {
            if (! is_string($attribute) || ! is_string($castType)) {
                continue;
            }

            if (isset($this->{$attribute}) && is_scalar($this->{$attribute})) {
                $newModel->{$attribute} = $this->castAttribute($attribute, $this->{$attribute});
            }
        }
    }
}
