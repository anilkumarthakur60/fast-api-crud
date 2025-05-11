<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PermissionModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;

describe(description: 'testing_post_model_factory', tests: function () {

    beforeEach(function () {
        $this->user = UserModel::factory()->create();
        $this->actingAs($this->user);
    });

    /** @test*/
    it(description: 'can_create_a_post_using_factory', closure: function () {
        $post = PostModel::factory()
            ->create(
                [
                    'name' => $inputName = 'Post 1',
                    'desc' => $inputDesc = 'Post 1 Description',
                    'status' => true,
                    'active' => false,
                    'user_id' => $this->user->id,
                ],
            );
        expect($post->name)
            ->toBe(expected: $inputName)
            ->and($post->desc)
            ->toBe(expected: $inputDesc)
            ->and($post->status)
            ->toBe(expected: true)
            ->and($post->active)
            ->toBe(expected: false);
        $this->assertDatabaseHas(table: 'posts', data: [
            'name' => $inputName,
            'desc' => $inputDesc,
            'status' => true,
            'active' => false,
            'deleted_at' => null,
        ]);
        expect($post->tags)
            ->toBeEmpty()
            ->and($post->tags()
                ->count())
            ->toBe(0);
    });
    it(description: 'can_update_a_post_using_factory', closure: function () {
        $post = PostModel::factory()
            ->create(
                [
                    'name' => 'Post 1',
                    'desc' => 'Post 1 Description',
                    'status' => 1,
                    'active' => 0,
                    'user_id' => $this->user->id,
                ],
            );
        $post->update(
            [
                'name' => $inputName = 'Post 2',
                'desc' => $inputDesc = 'Post 2 Description',
                'status' => $active = 0,
                'active' => $inActive = 1,
            ],
        );
        expect($post->name)
            ->toBe(expected: $inputName)
            ->and($post->desc)
            ->toBe(expected: $inputDesc)
            ->and($post->status)
            ->toBe(expected: $active)
            ->and($post->active)
            ->toBe(expected: $inActive)
            ->and($post->deleted_at)
            ->toBeNull();
        $this->assertDatabaseHas('posts', [
            'name' => $inputName,
            'desc' => $inputDesc,
            'status' => $active,
            'active' => $inActive,
            'deleted_at' => null,
        ]);
    });
    it(description: 'can_delete_a_post_using_factory', closure: function () {
        $post = PostModel::factory()
            ->create(
                [
                    'name' => $inputName = 'Post 1',
                    'desc' => $inputDesc = 'Post 1 Description',
                    'status' => 1,
                    'active' => 0,
                    'user_id' => $this->user->id,
                ]
            );
        $post->forceDelete();
        $this->assertDatabaseMissing('posts', [
            'name' => $inputName,
            'desc' => $inputDesc,
            'status' => 1,
            'active' => 0,
            'deleted_at' => null,
        ]);
    });
});
describe(description: 'test_post_controller', tests: function () {

    beforeEach(function () {
        $this->user = UserModel::factory()->create();
        $this->actingAs($this->user);
    });

    it(description: 'can_get_all_posts', closure: function () {
        $this->user->givePermissionTo(['view-posts']);
        PostModel::factory()
            ->createMany([
                [
                    'name' => 'Post 1',
                    'desc' => 'Post 1 Description',
                    'status' => 1,
                    'active' => 1,
                    'user_id' => $this->user->id,
                ],
                [
                    'name' => 'Post 2',
                    'desc' => 'Post 2 Description',
                    'status' => 0,
                    'active' => 0,
                    'user_id' => $this->user->id,
                ],
            ]);
        // dd($this->getJson('posts')->json());
        $this->get(uri: 'posts')
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonStructure(
                [
                    'data' => [
                        [
                            'id',
                            'name',
                            'desc',
                            'user_id',
                            'status',
                            'active',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next',
                    ],
                    'meta' => [
                        'current_page',
                        'from',
                        'last_page',
                        'path',
                        'per_page',
                        'to',
                        'total',
                    ],
                ]
            );
    });
    it(description: 'can_create_a_post_in_api', closure: function () {
        $this->user->givePermissionTo(['store-posts']);
        $post = PostModel::factory()
            ->raw(
                [
                    'status' => 1,
                    'active' => 0,
                    'user_id' => $this->user->id,
                    'desc' => 'Post 1 Description',
                ]
            );
        $response = $this->postJson(uri: 'posts', data: $post);
        $response->assertStatus(status: 201);
        $this->assertDatabaseHas(table: 'posts', data: [
            ...$post,
            'deleted_at' => null,
        ]);
    });
    it(description: 'can_update_a_post', closure: function () {
        $permission = PermissionModel::updateOrCreate(['name' => 'update-posts']);
        $this->user->permissions()->attach($permission->id);
        $post = PostModel::factory()
            ->create(
                [
                    'name' => 'Post 1',
                    'desc' => 'Post 1 Description',
                    'status' => 1,
                    'active' => 0,
                    'user_id' => $this->user->id,
                ]
            );
        $response = $this->putJson(uri: "posts/{$post->id}", data: $data = [
            'name' => 'Post 2',
            'desc' => 'Post 2 Description',
            'status' => 0,
            'active' => 1,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(status: 200);
        $this->assertDatabaseHas('posts', [
            ...$data,
            'deleted_at' => null,
        ]);
    });
    it(description: 'can_delete_a_post', closure: function () {
        $permission = PermissionModel::updateOrCreate(['name' => 'delete-posts']);
        $this->user->permissions()->attach($permission->id);
        $post = PostModel::factory()
            ->create([
                'name' => 'Post 1',
                'user_id' => $this->user->id,
            ]);
        $response = $this->deleteJson(uri: "posts/{$post->id}");
        $response->assertStatus(status: 204);
        $this->assertDatabaseHas('posts', [
            'name' => 'Post 1',
            'deleted_at' => now(),
        ]);
    });
    it(description: 'can_get_a_post', closure: function () {
        $permission = PermissionModel::updateOrCreate(['name' => 'view-posts']);
        $this->user->permissions()->attach($permission->id);
        $post = PostModel::factory()
            ->create();
        $response = $this->get(uri: 'posts/'.$post->id);
        $response->assertStatus(status: 200);
        $response->assertJson(['data' => ['name' => $post->name]]);
        $response->assertJsonStructure(
            [
                'data' => [
                    'id',
                    'name',
                    'desc',
                    'user_id',
                    'status',
                    'active',
                    'created_at',
                    'updated_at',
                ],
            ]
        );
    });
    it(description: 'can_post_a_post_with_tags_ids', closure: function () {

        $this->user->givePermissionTo(['store-posts']);
        $tagIds = TagModel::factory(2)
            ->create()
            ->modelKeys();
        $post = PostModel::factory()
            ->raw([
                'name' => 'Post with Tags',
                'desc' => 'Description of Post with Tags',
                'status' => 1,
                'active' => 0,
                'user_id' => $this->user->id,
            ]);
        $response = $this->postJson(uri: 'posts', data: [
            ...$post,
            'tag_ids' => $tagIds,
        ]);
        $response->assertStatus(status: 201);
        $this->assertDatabaseHas(table: 'posts', data: [
            ...$post,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas(table: 'post_tag', data: ['post_id' => $response->json('data.id'), 'tag_id' => $tagIds[0]]);
        $this->assertDatabaseHas(table: 'post_tag', data: ['post_id' => $response->json('data.id'), 'tag_id' => $tagIds[1]]);
        $this->assertSame(2, PostModel::query()->find($response->json('data.id'))->tags()->count());
    });
});
