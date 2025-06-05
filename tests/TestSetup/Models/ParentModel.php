<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Model
 */
class ParentModel extends Model
{
    use HasReplicatesWithRelation;

    protected $table = 'parents';

    protected $guarded = [];

    /**
     * Get all children for this parent.
     *
     * @return HasMany<ChildModel, ParentModel>
     */
    public function children(): HasMany
    {
        /** @var HasMany<ChildModel, ParentModel> */
        return $this->hasMany(ChildModel::class, 'parent_id');
    }
}
