<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;

describe('index query features', function () {
    it('eager loads allowlisted relations via the include filter', function () {
        $post = PostModel::factory()->create();
        $post->tags()->attach(TagModel::factory()->create()->id);

        // Without include the relation is not loaded/serialised.
        $this->getJson(route('posts.index'))
            ->assertOk()
            ->assertJsonMissingPath('data.0.tags');

        // String form: filters={"include":"tags"}
        $this->getJson(route('posts.index', ['filters' => json_encode(['include' => 'tags'])]))
            ->assertOk()
            ->assertJsonCount(1, 'data.0.tags');

        // Array form: filters={"include":["tags"]}
        $this->getJson(route('posts.index', ['filters' => json_encode(['include' => ['tags']])]))
            ->assertOk()
            ->assertJsonCount(1, 'data.0.tags');
    });

    it('ignores includes that are not on the allowlist', function () {
        PostModel::factory()->create();

        $this->getJson(route('posts.index', ['filters' => json_encode(['include' => 'comments,secret'])]))
            ->assertOk()
            ->assertJsonMissingPath('data.0.tags')
            ->assertJsonMissingPath('data.0.user');
    });

    it('filters soft-deleted records via the trashed filter', function () {
        PostModel::factory()->create();
        PostModel::factory()->create()->delete();

        $this->getJson(route('posts.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson(route('posts.index', ['filters' => json_encode(['trashed' => 'with'])]))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson(route('posts.index', ['filters' => json_encode(['trashed' => 'only'])]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('applies scopes, include and trashed together from one filters param', function () {
        $kept = PostModel::factory()->create(['active' => 1]);
        $kept->tags()->attach(TagModel::factory()->create()->id);
        PostModel::factory()->create(['active' => 1])->delete();

        $this->getJson(route('posts.index', [
            'filters' => json_encode([
                'active' => 1,
                'include' => 'tags',
                'trashed' => 'with',
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonCount(1, 'data.1.tags');
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
