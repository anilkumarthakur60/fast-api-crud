<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;

function createTag(array $attributes = []): TagModel
{
    return TagModel::factory()->create($attributes);
}

describe('tag_model_api', function () {
    describe('crud_operations', function () {
        it('creates_a_tag', function () {
            $attributes = [
                'name' => 'Tag 1',
                'desc' => 'Tag 1 Description',
                'status' => 1,
                'active' => 0,
            ];

            $tag = createTag($attributes);

            expect($tag->only(['name', 'desc', 'status', 'active']))->toMatchArray($attributes);
            $this->assertDatabaseHas('tags', $attributes);
        });

        it('updates_a_tag', function () {
            $tag = createTag([
                'name' => 'Tag 1',
                'desc' => 'Tag 1 Description',
                'status' => 1,
                'active' => 0,
            ]);

            $updatedAttributes = [
                'name' => 'Tag 2',
                'desc' => 'Tag 2 Description',
                'status' => 0,
                'active' => 1,
            ];

            $tag->update($updatedAttributes);

            expect($tag->only(['name', 'desc', 'status', 'active']))->toMatchArray($updatedAttributes);
            $this->assertDatabaseHas('tags', $updatedAttributes);
        });

        it('soft_deletes_a_tag', function () {
            $tag = createTag(['name' => 'Tag 1']);
            $tag->delete();

            $this->assertSoftDeleted('tags', ['id' => $tag->id]);
        });

        it('force_deletes_a_tag', function () {
            $tag = createTag(['name' => 'Tag 1']);
            $tag->forceDelete();

            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        });
    });

    describe('tag_api_endpoints', function () {
        it('retrieves_all_tags', function () {
            TagModel::factory()->createMany([
                ['name' => 'Tag 1', 'desc' => 'Tag 1 Description', 'status' => 1, 'active' => 1],
                ['name' => 'Tag 2', 'desc' => 'Tag 2 Description', 'status' => 0, 'active' => 0],
            ]);

            $this->get('tags')
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonStructure(['data', 'links', 'meta']);
        });

        it('filters_tags_by_query', function () {
            createTag(['name' => 'Tag 1', 'status' => 1, 'active' => 1]);
            createTag(['name' => 'Tag 2', 'status' => 0, 'active' => 0]);

            $response = $this->get('tags?filters='.json_encode(['queryFilter' => 'Tag 2']));

            $response
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['name' => 'Tag 2']);
        });

        it('creates_a_tag_via_api', function () {
            $attributes = [
                'name' => 'Tag 1',
                'desc' => 'Tag 1 Description',
                'status' => 1,
                'active' => 0,
            ];

            $this->postJson('tags', $attributes)
                ->assertCreated();

            $this->assertDatabaseHas('tags', $attributes);
        });

        it('validates_tag_name_during_creation', function () {
            $longName = str_repeat('A', 256);

            $this->postJson('tags', ['name' => $longName])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        });

        it('updates_a_tag_via_api', function () {
            $tag = createTag([
                'name' => 'Tag 1',
                'desc' => 'Tag 1 Description',
                'status' => 1,
                'active' => 0,
            ]);

            $updatedAttributes = [
                'name' => 'Tag 2',
                'desc' => 'Tag 2 Description',
                'status' => 0,
                'active' => 1,
            ];

            $this->putJson("tags/{$tag->id}", $updatedAttributes)
                ->assertOk();

            $this->assertDatabaseHas('tags', $updatedAttributes);
        });

        it('deletes_a_tag_via_api', function () {
            $tag = createTag(['name' => 'Tag 1']);

            $this->deleteJson("tags/{$tag->id}")
                ->assertNoContent();

            $this->assertSoftDeleted('tags', ['id' => $tag->id]);
        });
    });
});
