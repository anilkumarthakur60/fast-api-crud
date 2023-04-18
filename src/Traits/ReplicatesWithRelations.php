<?php

namespace Anil\ExceptionResponse\Traits;


trait ReplicatesWithRelations
{
    public function replicateWithRelations()
    {
        $newModel = $this->replicate();

        $newModel->save();

        foreach ($this->getRelations() as $relation => $models) {
            foreach ($models as $model) {
                $newModelRelation = $model->replicateWithRelations();
                $newModelRelation->{$model->{$model->getKeyName()}} = null;
                $newModel->{$relation}()->save($newModelRelation);
            }
        }

        return $newModel;
    }
}
