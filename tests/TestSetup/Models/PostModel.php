<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Database\Factories\PostModelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $desc
 * @property-read int $user_id
 * @property-read int $status
 * @property-read int $active
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Carbon $deleted_at
 *
 * @method static Builder<PostModel> initializer(bool $orderBy = true)
 * @method static Builder<PostModel> paginates(int $perPage = 15)
 * @method static Builder<PostModel> simplePaginates(int $perPage = 15)
 * @method static Builder<PostModel> likeWhere(array $attributes, ?string $searchTerm = null)
 *
 * @mixin Builder<PostModel>
 */
class PostModel extends Model
{
    /** @use HasFactory<PostModelFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'active',
        'desc',
        'name',
        'status',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return BelongsTo<UserModel,PostModel>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<UserModel,PostModel> */
        return $this->belongsTo(
            related: UserModel::class,
            foreignKey: 'user_id',
            ownerKey: 'id'
        );
    }

    /**
     * The tags that belong to the post.
     *
     * @return BelongsToMany<TagModel,PostModel>
     */
    public function tags(): BelongsToMany
    {
        /** @var BelongsToMany<TagModel,PostModel> */
        return $this->belongsToMany(
            related: TagModel::class,
            table: 'post_tag',
            foreignPivotKey: 'post_id',
            relatedPivotKey: 'tag_id',
            parentKey: 'id',
            relatedKey: 'id'
        );
    }

    public function afterCreate(): void
    {
        $request = request();
        if ($request->filled('tag_ids')) {
            $this->tags()->sync((array) $request->input('tag_ids'));
        }
    }

    public function afterUpdate(): void
    {
        $request = request();
        if ($request->filled('tag_ids')) {
            $this->tags()->sync((array) $request->input('tag_ids'));
        }
    }

    public function getPermissionSlug(): string
    {
        return 'posts';
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeQueryFilter(Builder $query, string $value): Builder
    {
        return $query->likeWhere(['name', 'desc'], $value);
    }
}
