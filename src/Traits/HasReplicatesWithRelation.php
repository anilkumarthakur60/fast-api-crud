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

trait HasReplicatesWithRelation
{
    /**
     * Replicate this model along with all of its loaded relations.
     *
     * - For BelongsTo / MorphTo: replicates the parent model and associates it.
     * - For HasOne / MorphOne: replicates the related model and saves it.
     * - For HasMany / MorphMany: replicates each related model and saves them.
     * - For BelongsToMany / MorphToMany: replicates the pivot associations (syncs related IDs).
     * - Throws an Exception for unsupported relationship types (e.g., HasManyThrough).
     *
     * @return static The newly replicated model instance (with relations replicated).
     *
     * @throws Exception
     */
    public function replicateWithRelations(): self
    {
        /** @var Model $this */
        // First, replicate the base model (without relations).
        $newModel = $this->replicate();

        // If there are castable attributes that need special handling, copy them over.
        foreach ($this->getMatchedCastableAttributes() as $attribute => $casts) {
            $newModel->{$attribute} = $this->castAttribute($attribute, $this->{$attribute}, $casts);
        }

        // Save the new model so that it has a primary key for relation associations.
        $newModel->save();

        // Loop through each loaded relation on the original model.
        foreach ($this->getRelations() as $relationName => $relationValue) {
            // Skip if the relation is null or empty.
            if (! $relationValue) {
                continue;
            }

            // Obtain the relation instance (not the loaded value).
            $relationInstance = $this->{$relationName}();

            // Handle different relation types:
            switch (true) {
                // ---------------------
                // BelongsTo / MorphTo
                // ---------------------
                case $relationInstance instanceof BelongsTo:
                case $relationInstance instanceof MorphTo:
                    /** @var Model $relatedModel */
                    $relatedModel = $relationValue;
                    // Recursively replicate the parent record (with its own relations).
                    $replicatedParent = $relatedModel->replicateWithRelations();
                    // Associate to the new child model and save.
                    $newModel->{$relationName}()->associate($replicatedParent);
                    $newModel->save();
                    break;

                    // -------------
                    // HasOne / MorphOne
                    // -------------
                case $relationInstance instanceof HasOne:
                case $relationInstance instanceof MorphOne:
                    /** @var Model $relatedModel */
                    $relatedModel = $relationValue;
                    // Recursively replicate the related model (and its relations).
                    $newRelated = $relatedModel->replicateWithRelations();
                    // Save via the hasOne/morphOne relation on the new model.
                    $newModel->{$relationName}()->save($newRelated);
                    break;

                    // ---------------
                    // HasMany / MorphMany
                    // ---------------
                case $relationInstance instanceof HasMany:
                case $relationInstance instanceof MorphMany:
                    /** @var Collection<int, Model> $relatedCollection */
                    $relatedCollection = $relationValue;
                    foreach ($relatedCollection as $childModel) {
                        // Recursively replicate each child model.
                        $newChild = $childModel->replicateWithRelations();
                        // Save them via the hasMany/morphMany relation.
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
                    // Gather IDs (keys) of related models to sync on the new model.
                    $ids = $relatedCollection->pluck(
                        $relationInstance->getRelated()->getKeyName()
                    )->toArray();
                    // Sync pivot table: attach existing related records to the new model.
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
     * Return an array of attribute => castType for scalar attributes that need re-applying casts.
     *
     * Only includes those attributes whose cast configuration is one of:
     *   - numeric: int, integer, real, float, double, decimal
     *   - json: json, array, object, collection
     *   - boolean: bool, boolean
     *   - string
     *   - object
     *   - collection
     *
     * And whose current value is actually of a type matching that cast.
     *
     * @return array<string,string> Keyed by attribute name, value is castType ("numeric", "json", etc.)
     */
    public function getMatchedCastableAttributes(): array
    {
        $matchedCastableAttributes = [];

        foreach ($this->getCasts() as $attribute => $castType) {
            $castType = strtolower(trim((string) $castType));

            // Normalize numeric casts
            if (in_array($castType, ['int', 'integer', 'real', 'float', 'double', 'decimal'], true)) {
                $castType = 'numeric';
            }
            // Normalize JSON-like casts
            if (in_array($castType, ['json', 'array', 'object', 'collection'], true)) {
                $castType = 'json';
            }

            // Only proceed if the attribute exists and is scalar/appropriate
            if (
                isset($this->{$attribute})
                && is_scalar($this->{$attribute})
                && $this->isCastable($this->{$attribute}, $castType)
            ) {
                $matchedCastableAttributes[$attribute] = $castType;
            }
        }

        return $matchedCastableAttributes;
    }

    /**
     * Determine if a given value matches a particular cast type.
     *
     * Supported $type values:
     *  - 'int' | 'integer'
     *  - 'real' | 'float' | 'double' | 'decimal'
     *  - 'bool' | 'boolean'
     *  - 'string'
     *  - 'array' | 'json'
     *  - 'object'
     *  - 'collection'
     *
     * @param  mixed  $value  The value to check.
     * @param  string  $type  The normalized cast type to verify.
     * @return bool True if the value can be considered castable to $type.
     */
    protected function isCastable(mixed $value, string $type): bool
    {
        return match ($type) {
            'int', 'integer' => is_numeric($value),
            'real', 'float', 'double', 'decimal' => is_numeric($value) || (is_string($value) && preg_match('/^-?\d+(\.\d+)?$/', $value)),
            'bool', 'boolean' => is_bool($value) || in_array(strtolower((string) $value), ['1', 'true', 'yes'], true),
            'string' => is_string($value),
            'array', 'json' => is_array($value) || (is_object($value) && method_exists($value, 'toArray')),
            'object' => is_object($value),
            'collection' => $value instanceof Collection,
            default => false,
        };
    }
}
