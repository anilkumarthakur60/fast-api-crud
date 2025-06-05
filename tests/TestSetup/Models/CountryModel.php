<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin Model
 */
class CountryModel extends Model
{
    use HasReplicatesWithRelation;

    protected $table = 'countries';

    protected $fillable = [];

    /**
     * The users that belong to the country.
     *
     * @return HasMany<UserModel, CountryModel>
     */
    public function users(): HasMany
    {
        /** @var HasMany<UserModel, CountryModel> */
        return $this->hasMany(UserModel::class, 'country_id');
    }

    /**
     * The posts that belong to the country via users.
     *
     * @return HasManyThrough<
     *     \Anil\FastApiCrud\Tests\TestSetup\Models\PostModel,
     *     \Anil\FastApiCrud\Tests\TestSetup\Models\UserModel,
     *     \Anil\FastApiCrud\Tests\TestSetup\Models\CountryModel
     * >
     */
    public function posts(): HasManyThrough
    {
        /** @var HasManyThrough<
         *     \Anil\FastApiCrud\Tests\TestSetup\Models\PostModel,
         *     \Anil\FastApiCrud\Tests\TestSetup\Models\UserModel,
         *     \Anil\FastApiCrud\Tests\TestSetup\Models\CountryModel
         * > */
        return $this->hasManyThrough(
            PostModel::class,
            UserModel::class,
            'country_id', // Foreign key on users table...
            'user_id'     // Foreign key on posts table...
        );
    }
}
