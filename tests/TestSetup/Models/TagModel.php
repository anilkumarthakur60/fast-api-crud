<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Tests\TestSetup\Factories\TagModelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagModel extends Model
{
    /** @use HasFactory<TagModelFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'desc',
        'status',
        'active',
    ];

    public function afterCreate(): void
    {
        $request = request();
        if ($request->filled('post_ids')) {
            $this->posts()->sync((array) $request->input('post_ids'));
        }
    }

    /**
     * The posts that belong to the tag.
     *
     * @return BelongsToMany<PostModel,TagModel>
     */
    public function posts(): BelongsToMany
    {
        /** @var BelongsToMany<PostModel,TagModel> */
        return $this->belongsToMany(
            related: PostModel::class,
            table: 'post_tag',
            foreignPivotKey: 'tag_id',
            relatedPivotKey: 'post_id',
            parentKey: 'id',
            relatedKey: 'id'
        );
    }

    public function afterUpdate(): void
    {
        $request = request();
        if ($request->filled('post_ids')) {
            $this->posts()->sync((array) $request->input('post_ids'));
        }
    }

    /**
     * @param  Builder<TagModel>  $query
     * @return Builder<TagModel>
     */
    public function scopeQueryFilter(Builder $query, mixed $search): Builder
    {
        return $query->likeWhere(
            attributes: ['name', 'desc'],
            searchTerm: $search
        );
    }

    /**
     * @param  Builder<TagModel>  $query
     * @return Builder<TagModel>
     */
    public function scopeActive(Builder $query, int $active = 1): Builder
    {
        return $query->where('active', $active);
    }

    /**
     * @param  Builder<TagModel>  $query
     * @return Builder<TagModel>
     */
    public function scopeStatus(Builder $query, int $status = 1): Builder
    {
        return $query->where('status', $status);
    }
}
