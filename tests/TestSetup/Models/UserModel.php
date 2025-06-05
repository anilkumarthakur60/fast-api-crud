<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Tests\TestSetup\Factories\UserModelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Request;

class UserModel extends Authenticatable
{
    /** @use HasFactory<UserModelFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'users';

    protected string $guard_name = 'web1';

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'active',
    ];

    protected $casts = [
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @param  Builder<UserModel>  $query
     * @return Builder<UserModel>
     */
    public function scopeQueryFilter(Builder $query, mixed $search): Builder
    {
        return $query->likeWhere(
            attributes: ['name', 'email'],
            searchTerm: $search
        );
    }

    /**
     * @param  Builder<UserModel>  $query
     * @return Builder<UserModel>
     */
    public function scopeActive(Builder $query, int $active = 1): Builder
    {
        return $query->where('active', $active);
    }

    /**
     * @param  Builder<UserModel>  $query
     * @return Builder<UserModel>
     */
    public function scopeStatus(Builder $query, int $status = 1): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<UserModel>  $query
     * @return Builder<UserModel>
     */
    public function scopeHasPosts(Builder $query): Builder
    {
        return $query->whereHas('posts');
    }

    public function afterCreate(): static
    {
        $request = Request::instance();
        if ($request->has('post')) {
            /** @var array<string, mixed> $postData */
            $postData = $request->input('post');
            $this->posts()->create([
                'name' => $postData['name'],
                'desc' => $postData['desc'],
                'status' => $postData['status'],
                'active' => $postData['active'],
            ]);
        }

        return $this;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return HasMany<PostModel,UserModel>
     */
    public function posts(): HasMany
    {
        /** @var HasMany<PostModel,UserModel> */
        return $this->hasMany(
            related: PostModel::class,
            foreignKey: 'user_id',
            localKey: 'id'
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasPermissionTo(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * @return BelongsToMany<PermissionModel,UserModel>
     */
    public function permissions(): BelongsToMany
    {
        /** @var BelongsToMany<PermissionModel, UserModel> */
        return $this->belongsToMany(
            related: PermissionModel::class,
            table: 'user_permission',
            foreignPivotKey: 'user_id',
            relatedPivotKey: 'permission_id'
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    public function givePermissionTo(array $permissions): static
    {
        foreach ($permissions as $permission) {
            $this->permissions()->firstOrCreate(['name' => $permission]);
        }
        $permissions = PermissionModel::query()->whereIn('name', $permissions)->pluck('id')->toArray();
        $this->permissions()->syncWithoutDetaching($permissions);

        return $this;
    }

    /**
     * @param  list<string>  $permissions
     */
    public function revokePermissionTo(array $permissions): static
    {
        $permissions = PermissionModel::query()->whereIn('name', $permissions)->pluck('id')->toArray();
        $this->permissions()->detach($permissions);

        return $this;
    }

    /**
     * The profile that belongs to the user.
     *
     * @return MorphOne<ProfileModel, UserModel>
     */
    public function profile(): MorphOne
    {
        /** @var MorphOne<ProfileModel, UserModel> */
        return $this->morphOne(ProfileModel::class, 'profilable');
    }

    /**
     * The comments that belong to the user.
     *
     * @return MorphMany<CommentModel, UserModel>
     */
    public function comments(): MorphMany
    {
        /** @var MorphMany<CommentModel, UserModel> */
        return $this->morphMany(CommentModel::class, 'commentable');
    }
}
