<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;

describe('index query features', function () {
    it('eager loads allowlisted relations via the include parameter', function () {
        $post = PostModel::factory()->create();
        $post->tags()->attach(TagModel::factory()->create()->id);

        // Without ?include the relation is not loaded/serialised.
        $this->getJson(route('posts.index'))
            ->assertOk()
            ->assertJsonMissingPath('data.0.tags');

        // With ?include=tags the relation is eager loaded and serialised.
        $this->getJson(route('posts.index', ['include' => 'tags']))
            ->assertOk()
            ->assertJsonCount(1, 'data.0.tags');
    });

    it('ignores includes that are not on the allowlist', function () {
        PostModel::factory()->create();

        $this->getJson(route('posts.index', ['include' => 'comments,secret']))
            ->assertOk()
            ->assertJsonMissingPath('data.0.tags')
            ->assertJsonMissingPath('data.0.user');
    });

    it('filters soft-deleted records via the trashed parameter', function () {
        PostModel::factory()->create();
        PostModel::factory()->create()->delete();

        $this->getJson(route('posts.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson(route('posts.index', ['trashed' => 'with']))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson(route('posts.index', ['trashed' => 'only']))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('caps bulk delete at the configured max_rows', function () {
        config(['fast-api.bulk.max_rows' => 2]);

        $posts = PostModel::factory()->count(3)->create();

        $this->postJson(route('posts.delete'), [
            'delete_rows' => $posts->pluck('id')->all(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['delete_rows']);
    });
});
