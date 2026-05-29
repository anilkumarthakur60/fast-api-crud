<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;

describe('replicateWithRelations', function () {
    it('replicates a hasMany graph and sets the non-null child foreign key', function () {
        $user = UserModel::factory()->create();
        PostModel::factory()->count(2)->create(['user_id' => $user->id]);

        $clone = $user->load('posts')->replicateWithRelations();

        expect($clone->id)->not->toBe($user->id)
            ->and($clone->posts()->count())->toBe(2)
            // children point at the NEW parent
            ->and(PostModel::query()->where('user_id', $clone->id)->count())->toBe(2)
            // originals are untouched
            ->and(PostModel::query()->where('user_id', $user->id)->count())->toBe(2);

        // 2 original + 2 cloned posts, no orphan/duplicate inserts
        expect(PostModel::query()->count())->toBe(4);
    });

    it('replicates belongsTo by associating the same parent', function () {
        $user = UserModel::factory()->create();
        $post = PostModel::factory()->create(['user_id' => $user->id]);

        $clone = $post->load('user')->replicateWithRelations();

        expect($clone->id)->not->toBe($post->id)
            ->and($clone->user_id)->toBe($user->id)
            // the parent was associated, not duplicated
            ->and(UserModel::query()->count())->toBe(1);
    });

    it('replicates belongsToMany by syncing the same related records', function () {
        $user = UserModel::factory()->create();
        $post = PostModel::factory()->create(['user_id' => $user->id]);
        $tags = TagModel::factory()->count(2)->create();
        $post->tags()->sync($tags->pluck('id')->all());

        $clone = $post->load('tags')->replicateWithRelations();

        expect($clone->id)->not->toBe($post->id)
            ->and($clone->tags()->count())->toBe(2)
            ->and($clone->tags->pluck('id')->sort()->values()->all())
            ->toBe($tags->pluck('id')->sort()->values()->all())
            // tags were attached, not duplicated
            ->and(TagModel::query()->count())->toBe(2);
    });

    it('does not copy the primary key or timestamps onto the replica', function () {
        $user = UserModel::factory()->create();

        $clone = $user->replicateWithRelations();

        expect($clone->id)->not->toBe($user->id)
            ->and(UserModel::query()->count())->toBe(2);
    });
});
