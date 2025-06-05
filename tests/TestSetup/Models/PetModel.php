<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PetModel extends Model
{
    use HasReplicatesWithRelation;

    protected $table = 'owners';

    protected $fillable = [
        'owner_id',
        'pet_name',
    ];

    /**
     * The owner that belongs to the pet.
     *
     * @return HasOne<OwnerModel, PetModel>
     */
    public function owner(): HasOne
    {
        /** @var HasOne<OwnerModel, PetModel> */
        return $this->hasOne(OwnerModel::class, 'owner_id');
    }
}
