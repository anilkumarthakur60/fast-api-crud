<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
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
 * Child records (HasOne/HasMany/MorphOne/MorphMany) are persisted *through* the
 * parent relation, so their foreign key is set before the row is inserted —
 * this works even when the foreign key column is NOT NULL. Only relations that
 * are actually loaded on the source model are replicated.
 *
 * @phpstan-require-extends Model
 */
trait ReplicatesWithRelations
{
    /**
     * Replicate this model along with its loaded (or given) relations.
     *
     * @param array<int, string> $relations Relations to replicate (dot notation for depth). Empty = use loaded relations.
     * @param array<int, string> $except Attributes to exclude from the replica.
     *
     * @throws Exception
     *
     * @return static The newly saved replica.
     */
    public function replicateWithRelations(array $relations = [], array $except = []): static
    {
        /** @var SplObjectStorage<Model, Model> $visited */
        $visited = new SplObjectStorage;

        return $this->replicateWithRelationsUsing($relations, $except, $visited);
    }

    /**
     * Replication with circular-reference tracking.
     *
     * Must be public so a parent model can drive replication of child models of
     * a different class during recursion. Treat as internal — use
     * {@see replicateWithRelations()} as the entry point.
     *
     * @internal
     *
     * @param array<int, string> $relations
     * @param array<int, string> $except
     * @param SplObjectStorage<Model, Model> $visited Tracks original→clone to prevent infinite loops.
     * @param (Closure(Model): void)|null $persist How to persist the replica. Null = standalone save;
     *                                             otherwise the parent relation saves it (setting the FK).
     *
     * @throws Exception
     */
    public function replicateWithRelationsUsing(array $relations, array $except, SplObjectStorage $visited, ?Closure $persist = null): static
    {
        // Circular reference guard — return the existing clone if we've seen this model.
        if ($visited->contains($this)) {
            /** @var static */
            return $visited[$this];
        }

        if ($relations !== []) {
            $this->loadMissing($relations);
        }

        $replica = $this->replicate($except !== [] ? $except : null);
        $this->reApplyCasts($replica);

        // Persist the replica — through the parent relation (FK set before insert)
        // when given a persist callback, otherwise as a standalone root record.
        if ($persist !== null) {
            $persist($replica);
        } else {
            $replica->save();
        }

        // Register in the visited map before recursing (handles circular refs).
        $visited[$this] = $replica;

        foreach ($this->getRelations() as $relationName => $relationValue) {
            if (! is_string($relationName) || ! $relationValue || ! method_exists($this, $relationName)) {
                continue;
            }

            $relationInstance = $this->{$relationName}();

            if (! $relationInstance instanceof Relation) {
                continue;
            }

            match (true) {
                // BelongsTo / MorphTo (MorphTo extends BelongsTo): point at the same parent.
                $relationInstance instanceof BelongsTo => $this->replicateBelongsTo($replica, $relationName, $relationValue),

                // Owned children: deep-replicate and attach to the new parent.
                $relationInstance instanceof HasOne,
                $relationInstance instanceof MorphOne,
                $relationInstance instanceof HasMany,
                $relationInstance instanceof MorphMany => $this->replicateChildren($replica, $relationName, $relationValue, $visited),

                // Many-to-many: sync the same related records, preserving pivot data.
                $relationInstance instanceof BelongsToMany => $this->replicateBelongsToMany($replica, $relationName, $relationValue, $relationInstance),

                // "Through" relations are derived, not owned — nothing to replicate.
                default => null,
            };
        }

        return $replica;
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
     * HasOne / HasMany / MorphOne / MorphMany — deep-replicate each child and
     * persist it through the new parent's relation so the foreign key is set
     * before the row is inserted.
     *
     * @param SplObjectStorage<Model, Model> $visited
     *
     * @throws Exception
     */
    private function replicateChildren(Model $newParent, string $relationName, mixed $relationValue, SplObjectStorage $visited): void
    {
        $children = $relationValue instanceof Collection ? $relationValue->all() : [$relationValue];

        foreach ($children as $child) {
            if (! $child instanceof Model) {
                continue;
            }

            $persist = function (Model $childReplica) use ($newParent, $relationName): void {
                $relation = $newParent->{$relationName}();

                if ($relation instanceof HasOneOrMany) {
                    $relation->save($childReplica);
                }
            };

            if (method_exists($child, 'replicateWithRelationsUsing')) {
                // Child uses this trait: recurse so its own loaded relations are replicated too.
                $child->replicateWithRelationsUsing([], [], $visited, $persist);

                continue;
            }

            // Child does not use the trait: replicate the row only.
            $this->replicateSimpleChild($child, $visited, $persist);
        }
    }

    /**
     * Replicate a single child that does not use this trait, persisting it
     * through the parent relation supplied by the caller.
     *
     * @param SplObjectStorage<Model, Model> $visited
     * @param Closure(Model): void $persist
     */
    private function replicateSimpleChild(Model $child, SplObjectStorage $visited, Closure $persist): void
    {
        if ($visited->contains($child)) {
            return;
        }

        $replica = $child->replicate();
        $persist($replica);

        $visited[$child] = $replica;
    }

    /**
     * BelongsToMany / MorphToMany — sync the same related records with pivot data preserved.
     *
     * @param BelongsToMany<Model, Model>|MorphToMany<Model, Model> $relationInstance
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

        $pivotColumns = $relationInstance->getPivotColumns();
        /** @var array<int|string, array<string, mixed>> $syncData */
        $syncData = [];

        foreach ($relationValue as $relatedModel) {
            if (! $relatedModel instanceof Model) {
                continue;
            }

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
     * Re-apply castable attributes to the replicated model.
     *
     * Only attributes that survived replicate() are touched — this avoids
     * re-introducing the primary key, timestamps, and other unique-id columns,
     * which getCasts() reports (e.g. "id" => "int") but replicate() excludes.
     */
    private function reApplyCasts(Model $newModel): void
    {
        $replicaAttributes = $newModel->getAttributes();

        foreach ($this->getCasts() as $attribute => $castType) {
            if (! is_string($attribute) || ! is_string($castType)) {
                continue;
            }

            if (! array_key_exists($attribute, $replicaAttributes)) {
                continue;
            }

            if (isset($this->{$attribute}) && is_scalar($this->{$attribute})) {
                $newModel->{$attribute} = $this->castAttribute($attribute, $this->{$attribute});
            }
        }
    }
}
