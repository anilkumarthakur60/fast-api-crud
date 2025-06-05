<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
class ChildModel extends Model
{
    use HasReplicatesWithRelation;

    protected $table = 'children';

    protected $fillable = [
        'parent_id',
        'title',
    ];

    /**
     * Get the parent that this child belongs to.
     *
     * @return BelongsTo<ParentModel, ChildModel>
     */
    public function parent(): BelongsTo
    {
        /** @var BelongsTo<ParentModel, ChildModel> */
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
}
