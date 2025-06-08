<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommentModel extends Model
{
    use HasReplicatesWithRelation;

    protected $table = 'comments';

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'body',
    ];

    /**
     * The model that this comment belongs to.
     *
     * @return MorphTo<Model, CommentModel>
     */
    public function commentable(): MorphTo
    {
        /** @var MorphTo<Model, CommentModel> */
        return $this->morphTo();
    }
}
