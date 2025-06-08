<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProfileModel extends Model
{
    use HasReplicatesWithRelation;

    protected $table = 'profiles';

    protected $fillable = [
        'profilable_id',
        'profilable_type',
        'bio',
    ];

    /**
     * The model that this profile belongs to.
     *
     * @return MorphTo<Model, ProfileModel>
     */
    public function profilable(): MorphTo
    {
        /** @var MorphTo<Model, ProfileModel> */
        return $this->morphTo();
    }
}
