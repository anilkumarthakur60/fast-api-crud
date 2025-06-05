<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OwnerModel extends Model
{
    use HasReplicatesWithRelation;

    protected $table = 'owners';

    protected $guarded = [];

    /**
     * The pet that belongs to the owner.
     *
     * @return HasOne<PetModel, OwnerModel>
     */
    public function pet(): HasOne
    {
        /** @var HasOne<PetModel, OwnerModel> */
        return $this->hasOne(PetModel::class, 'owner_id');
    }
}
