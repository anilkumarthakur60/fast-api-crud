<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PhotoModel extends Model
{
    protected $table = 'photos';

    protected $fillable = [];

    /**
     * The model that this photo belongs to.
     *
     * @return MorphTo<Model, PhotoModel>
     */
    public function imageable(): MorphTo
    {
        /** @var MorphTo<Model, PhotoModel> */
        return $this->morphTo();
    }
}
