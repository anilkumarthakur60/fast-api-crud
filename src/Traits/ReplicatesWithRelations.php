<?php

namespace Anil\FastApiCrud\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
trait ReplicatesWithRelations
{
    /**
     * @throws \Exception
     */
    public function replicateWithRelations(): self
    {
        $newModel = $this->replicate();

        foreach ($this->getMatchedCastableAttributes() as $attribute => $casts) {
            $newModel->{$attribute} = $this->castAttribute($attribute, $this->{$attribute}, $casts);
        }

        $newModel->save();

        foreach ($this->getRelations() as $relationName => $relationValue) {
            if (! $relationValue) {
                continue;
            }

            switch (true) {
                case $relationValue instanceof BelongsTo:
                    $newModel->{$relationName}()->associate($relationValue->getRelated()->replicateWithRelations());
                    break;
                case $relationValue instanceof HasOne:
                case $relationValue instanceof MorphMany:
                case $relationValue instanceof MorphOne:
                case $relationValue instanceof HasMany:
                    foreach ($relationValue as $model) {
                        $newModelRelation = $model->replicateWithRelations();
                        $newModel->{$relationName}()->save($newModelRelation);
                    }
                    break;
                case $relationValue instanceof BelongsToMany:
                    $ids = $relationValue->pluck($relationValue->getModel()->getKeyName())->toArray();
                    $newModel->{$relationName}()->sync($ids);
                    break;
                case $relationValue instanceof MorphTo:
                    $relatedModel = $relationValue->getRelated();
                    $newModelRelation = $relatedModel->replicateWithRelations();
                    $newModel->{$relationName}()->associate($newModelRelation);
                    break;
                default:
                    throw new \Exception('Relation not found');
                    break;
            }
        }

        return $newModel;
    }

    public function getMatchedCastableAttributes(): array
    {
        $matchedCastableAttributes = [];
        foreach ($this->getCasts() as $attribute => $castType) {
            $castType = strtolower(trim($castType));
            if (in_array($castType, ['int', 'integer', 'real', 'float', 'double', 'decimal'])) {
                $castType = 'numeric';
            }
            if (in_array($castType, ['json', 'array', 'object', 'collection'])) {
                $castType = 'json';
            }
            if (isset($this->{$attribute}) && is_scalar($this->{$attribute}) && $this->isCastable($this->{$attribute}, $castType)) {
                $matchedCastableAttributes[$attribute] = $castType;
            }
        }

        return $matchedCastableAttributes;
    }

    protected function isCastable($value, $type): bool
    {
        return match ($type) {
            'int', 'integer' => is_numeric($value),
            'real', 'float', 'double', 'decimal' => is_numeric($value) || is_string($value) && preg_match('/^-?\d+(\.\d+)?$/', $value),
            'bool', 'boolean' => is_bool($value) || in_array(strtolower($value), ['1', 'true', 'yes']),
            'string' => is_string($value),
            'array', 'json' => is_array($value) || is_object($value) && method_exists($value, 'toArray'),
            'object'     => is_object($value),
            'collection' => $value instanceof Collection,
            default      => false,
        };
    }
}
