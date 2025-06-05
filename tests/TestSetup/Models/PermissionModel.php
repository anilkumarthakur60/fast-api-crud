<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @method static Builder<Model> initializer(bool $orderBy = true)
 * @method static Builder<Model> paginates(int $perPage = 15)
 * @method static Builder<Model> simplePaginates(int $perPage = 15)
 * @method static Builder<Model> likeWhere(array<string> $attributes, ?string $searchTerm = null)
 *
 * @mixin Builder<Model>
 */
class PermissionModel extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsToMany<UserModel,PermissionModel>
     */
    public function users(): BelongsToMany
    {
        /** @var BelongsToMany<UserModel,PermissionModel> */
        return $this->belongsToMany(
            related: UserModel::class,
            table: 'user_permission',
            foreignPivotKey: 'permission_id',
            relatedPivotKey: 'user_id'
        );
    }
}
