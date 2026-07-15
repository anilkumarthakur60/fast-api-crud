<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;

describe('filters param cannot invoke arbitrary model methods', function () {
    it('ignores non-scope filter keys and never triggers a model write (e.g. save)', function () {
        PostModel::factory()->count(2)->create();

        // On the vulnerable code this key reached Model::save([]) via the
        // method_exists fallback and attempted an INSERT. It must now be ignored.
        $this->getJson(route('posts.index', ['filters' => json_encode(['save' => []])]))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        expect(PostModel::count())->toBe(2); // no phantom row was created
    });

    it('still applies declared named scopes from the filters param', function () {
        PostModel::factory()->create(['name' => 'Alpha', 'desc' => 'first']);
        PostModel::factory()->create(['name' => 'Bravo', 'desc' => 'second']);

        $this->getJson(route('posts.index', ['filters' => json_encode(['queryFilter' => 'Alpha'])]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('updateColumn is restricted to an allowlist', function () {
    it('allows updating an allowlisted column', function () {
        $post = PostModel::factory()->create(['status' => 1]);

        $this->putJson(
            route('posts.updateColumn', ['id' => $post->id, 'column' => 'status']),
            ['status' => 0],
        )->assertOk();

        expect((int) $post->fresh()->status)->toBe(0);
    });

    it('rejects updating a fillable-but-not-allowlisted column', function () {
        $post = PostModel::factory()->create(['name' => 'original']);

        $this->putJson(
            route('posts.updateColumn', ['id' => $post->id, 'column' => 'name']),
            ['name' => 'hacked'],
        )->assertForbidden();

        expect($post->fresh()->name)->toBe('original');
    });
});
