<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;

describe('configurable query parameter keys', function () {
    it('reads pagination and sort from custom configured keys', function () {
        config([
            'fast-api.query.per_page' => 'limit',
            'fast-api.query.sort_by' => 'order_by',
            'fast-api.query.descending' => 'desc',
        ]);

        TagModel::factory()->create(['name' => 'Alpha']);
        TagModel::factory()->create(['name' => 'Bravo']);
        TagModel::factory()->create(['name' => 'Charlie']);

        // custom per-page key
        $this->getJson('tags?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);

        // custom sort keys (ascending by name)
        $names = collect($this->getJson('tags?order_by=name&desc=false')->json('data'))
            ->pluck('name')
            ->all();
        expect($names)->toBe(['Alpha', 'Bravo', 'Charlie']);
    });

    it('reads the filters payload from a custom configured key', function () {
        config(['fast-api.query.filters' => 'where']);

        TagModel::factory()->create(['name' => 'Keep', 'status' => 1]);
        TagModel::factory()->create(['name' => 'Drop', 'status' => 0]);

        $this->getJson('tags?where='.urlencode(json_encode(['queryFilter' => 'Keep'])))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Keep');
    });

    it('reads the bulk delete field from a custom configured key', function () {
        config(['fast-api.bulk.field' => 'ids']);

        $tags = TagModel::factory()->count(2)->create();

        $this->postJson('tags/delete', ['ids' => $tags->pluck('id')->all()])
            ->assertNoContent();

        foreach ($tags as $tag) {
            $this->assertSoftDeleted('tags', ['id' => $tag->id]);
        }
    });
});
