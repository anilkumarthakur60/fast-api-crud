<?php

namespace Anil\FastApiCrud\Traits;

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
use Illuminate\Support\Collection;

/**
 * @mixin Model
 */
trait HasReplicatesWithRelation
{
    /**
     * Replicate this model along with all of its loaded relations.
     *
     * - BelongsTo / MorphTo: replicate the parent model and associate it.
     * - HasOne / MorphOne: replicate the related model and save it.
     * - HasMany / MorphMany: replicate each related model and save them.
     * - BelongsToMany / MorphToMany: replicate the pivot associations (sync related IDs).
     * - Throws an Exception for unsupported relation types (e.g., HasManyThrough).
     *
     * @return static The newly replicated model instance, with relations replicated.
     * @throws Exception
     */
    public function replicateWithRelations(): self
    {
        /** @var static $newModel */
        $newModel = $this->replicate();

        // Copy over castable attributes (re-applying built-in casts)
        foreach ($this->getMatchedCastableAttributes() as $attribute => $_castType) {
            // The base Model::castAttribute method expects (string $key, mixed $value)
            $newModel->{$attribute} = $this->castAttribute($attribute, $this->{$attribute});
        }

        $newModel->save();

        foreach ($this->getRelations() as $relationName => $relationValue) {
            if (! $relationValue) {
                continue;
            }

            /** @var \Illuminate\Database\Eloquent\Relations\Relation<Model, Model> $relationInstance */
            $relationInstance = $this->{$relationName}();

            switch (true) {
                // ---------------------
                // BelongsTo / MorphTo
                // ---------------------
                case $relationInstance instanceof BelongsTo:
                case $relationInstance instanceof MorphTo:
                    /** @var Model&HasReplicatesWithRelation $relatedModel */
                    $relatedModel = $relationValue;
                    $replicatedParent = $relatedModel->replicateWithRelations();
                    $newModel->{$relationName}()->associate($replicatedParent);
                    $newModel->save();
                    break;

                // -------------
                // HasOne / MorphOne
                // -------------
                case $relationInstance instanceof HasOne:
                case $relationInstance instanceof MorphOne:
                    /** @var Model&HasReplicatesWithRelation $relatedModel */
                    $relatedModel = $relationValue;
                    $newRelated = $relatedModel->replicateWithRelations();
                    $newModel->{$relationName}()->save($newRelated);
                    break;

                // ---------------
                // HasMany / MorphMany
                // ---------------
                case $relationInstance instanceof HasMany:
                case $relationInstance instanceof MorphMany:
                    /** @var Collection<int, Model&HasReplicatesWithRelation> $relatedCollection */
                    $relatedCollection = $relationValue;
                    foreach ($relatedCollection as $childModel) {
                        $newChild = $childModel->replicateWithRelations();
                        $newModel->{$relationName}()->save($newChild);
                    }
                    break;

                // -------------------
                // BelongsToMany / MorphToMany
                // -------------------
                case $relationInstance instanceof BelongsToMany:
                case $relationInstance instanceof MorphToMany:
                    /** @var Collection<int, Model> $relatedCollection */
                    $relatedCollection = $relationValue;
                    $ids = $relatedCollection->pluck(
                        $relationInstance->getRelated()->getKeyName()
                    )->toArray();
                    $newModel->{$relationName}()->sync($ids);
                    break;

                // --------------
                // HasOneThrough
                // --------------
                case $relationInstance instanceof HasOneThrough:
                    throw new Exception("HasOneThrough relationship '{$relationName}' is not supported for replication.");

                    // ---------------
                    // HasManyThrough
                    // ---------------
                case $relationInstance instanceof HasManyThrough:
                    throw new Exception("HasManyThrough relationship '{$relationName}' is not supported for replication.");

                    // -----------------------
                    // Fallback for unknown relation types
                    // -----------------------
                default:
                    $relClass = get_class($relationInstance);
                    throw new Exception("Relation '{$relationName}' of type '{$relClass}' is not supported for replication.");
            }
        }

        return $newModel;
    }

    /**
     * Return an array of attribute => normalized castType for scalar attributes that need re-applying casts.
     *
     * Only includes attributes whose cast type (from getCasts()) is one of:
     * - numeric: int|integer|real|float|double|decimal
     * - json: json|array|object|collection
     * - boolean: bool|boolean
     * - string
     * - object
     * - collection
     *
     * And only if the current attribute value is scalar and matches that normalized type.
     *
     * @return array<string,string> Attribute name => normalized cast type.
     */
    public function getMatchedCastableAttributes(): array
    {
        $matchedCastableAttributes = [];

        foreach ($this->getCasts() as $attribute => $castType) {
            $normalized = strtolower(trim((string) $castType));

            if (in_array($normalized, ['int', 'integer', 'real', 'float', 'double', 'decimal'], true)) {
                $normalized = 'numeric';
            }
            if (in_array($normalized, ['json', 'array', 'object', 'collection'], true)) {
                $normalized = 'json';
            }

            if (
                isset($this->{$attribute})
                && is_scalar($this->{$attribute})
                && $this->isCastable($this->{$attribute}, $normalized)
            ) {
                $matchedCastableAttributes[$attribute] = $normalized;
            }
        }

        return $matchedCastableAttributes;
    }

    /**
     * Determine if a given value matches a particular normalized cast type.
     *
     * Supported $type values:
     * - 'int', 'integer'
     * - 'real', 'float', 'double', 'decimal'
     * - 'bool', 'boolean'
     * - 'string'
     * - 'array', 'json'
     * - 'object'
     * - 'collection'
     *
     * @param  int|float|string|bool|array|object  $value  The value to check.
     * @param  string                                        $type   The normalized cast type.
     * @return bool         True if $value can be considered castable to $type.
     */
    protected function isCastable(int|float|string|bool|array|object $value, string $type): bool
    {
        return match ($type) {
            'int', 'integer' =>
            is_numeric($value),

            'real', 'float', 'double', 'decimal' =>
            is_numeric($value)
                || (is_string($value) && preg_match('/^-?\d+(\.\d+)?$/', $value)),

            'bool', 'boolean' =>
            is_bool($value)
                || in_array(strtolower((string) $value), ['1', 'true', 'yes'], true),

            'string' =>
            is_string($value),

            'array', 'json' =>
            is_array($value)
                || (is_object($value) && method_exists($value, 'toArray')),

            'object' =>
            is_object($value),

            'collection' =>
            $value instanceof Collection,

            default =>
            false,
        };
    }
}
