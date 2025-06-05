<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;

describe(description: 'user_model_feature_test', tests: function () {
    it(description: 'can_create_a_user', closure: function () {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'active' => true,
            'status' => true,
        ];

        $user = UserModel::create($userData);

        expect($user)->toBeInstanceOf(UserModel::class)
            ->and($user->name)->toBe('John Doe')
            ->and(Hash::check('password123', $user->password))->toBeTrue()
            ->and($user->active)->toBeTrue()
            ->and($user->status)->toBeTrue();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
            'status' => true,
        ]);
    });
    it(description: 'can_create_a_user_using_factory', closure: function () {
        $user = UserModel::factory()
            ->create(
                [
                    'name' => $inputName = 'John Doe',
                    'email' => $inputEmail = 'john@example.com',
                    'password' => 'password123',
                    'status' => true,
                    'active' => false,
                ],
            )->fresh();
        expect($user->name)
            ->toBe(expected: $inputName)
            ->and($user->email)
            ->toBe(expected: $inputEmail)
            ->and($user->status)
            ->toBe(expected: 1)
            ->and($user->active)
            ->toBe(expected: 0);
        $this->assertDatabaseHas(table: 'users', data: [
            'name' => $inputName,
            'email' => $inputEmail,
            'status' => true,
            'active' => false,
        ]);
        expect($user->posts)
            ->toBeEmpty()
            ->and($user->posts()
                ->count())
            ->toBe(0);
    });
    it(description: 'can_update_a_user_using_factory', closure: function () {
        $user = UserModel::factory()
            ->create(
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => 'password123',
                    'status' => 1,
                    'active' => 0,
                ],
            )->fresh();
        $user->update(
            [
                'name' => $inputName = 'Jane Doe',
                'email' => $inputEmail = 'jane@example.com',
                'status' => $active = 0,
                'active' => $inActive = 1,
            ],
        );
        expect($user->name)
            ->toBe(expected: $inputName)
            ->and($user->email)
            ->toBe(expected: $inputEmail)
            ->and($user->status)
            ->toBe(expected: $active)
            ->and($user->active)
            ->toBe(expected: $inActive);
        $this->assertDatabaseHas('users', [
            'name' => $inputName,
            'email' => $inputEmail,
            'status' => $active,
            'active' => $inActive,
        ]);
    });
    it(description: 'can_delete_a_user_using_factory', closure: function () {
        $user = UserModel::factory()
            ->create(
                [
                    'name' => $inputName = 'John Doe',
                    'email' => $inputEmail = 'john@example.com',
                    'password' => $inputPassword = 'password123',
                    'status' => $active = true,
                    'active' => $inActive = false,
                ]
            )->fresh();
        $user->forceDelete();
        $this->assertDatabaseMissing('users', [
            'name' => $inputName,
            'email' => $inputEmail,
            'password' => $inputPassword,
            'status' => $active,
            'active' => $inActive,
        ]);
    });
});
describe(description: 'user_model_api_test', tests: function () {
    it(description: 'can_get_all_users', closure: function () {
        UserModel::factory()
            ->createMany([
                [
                    'name' => 'John Doe1',
                    'email' => 'john1@example.com',
                    'password' => 'password123',
                    'status' => 1,
                    'active' => 1,
                ],
                [
                    'name' => 'John Doe2',
                    'email' => 'john2@example.com',
                    'password' => 'password123',
                    'status' => 0,
                    'active' => 0,
                ],
                [
                    'name' => 'John Doe3',
                    'email' => 'john3@example.com',
                    'password' => 'password123',
                    'status' => 1,
                    'active' => 0,
                ],
                [
                    'name' => 'John Doe4',
                    'email' => 'john4@example.com',
                    'password' => 'password123',
                    'status' => 0,
                    'active' => 1,
                ],
                [
                    'name' => 'John Doe5',
                    'email' => 'john5@example.com',
                    'password' => 'password123',
                    'status' => 1,
                    'active' => 1,
                ],
                [
                    'name' => 'John Doe6',
                    'email' => 'john6@example.com',
                    'password' => 'password123',
                    'status' => 0,
                    'active' => 0,
                ],
                [
                    'name' => 'John Doe7',
                    'email' => 'john7@example.com',
                    'password' => 'password123',
                    'status' => 1,
                    'active' => 0,
                ],
                [
                    'name' => 'John Doe8',
                    'email' => 'john8@example.com',
                    'password' => 'password123',
                    'status' => 0,
                    'active' => 1,
                ],
            ])->fresh();
        $jsonStructure = [
            'data' => [
                [
                    'id',
                    'name',
                    'email',
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
        ];
        $noDataJsonStructure = array_merge($jsonStructure, [
            'data' => [],
        ]);
        $this->get(uri: route('users.index', [
            'page' => 1,
            'rowsPerPage' => 10,
        ]))
            ->assertOk()
            ->assertJsonCount(count: 8, key: 'data')
            ->assertJsonStructure($jsonStructure);
        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'active' => 0,
                'status' => 0,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonStructure($jsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'active' => 1,
                'status' => 1,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonStructure($jsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'active' => 0,
                'status' => 1,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonStructure($jsonStructure);
        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'active' => 1,
                'status' => 0,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonStructure($jsonStructure);
        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'active' => 1,
                'status' => 0,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonStructure($jsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'queryFilter' => 'John Doe2',
                'active' => 0,
                'status' => 0,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 1, key: 'data')
            ->assertJsonStructure($jsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'queryFilter' => 'John Doe1',
                'active' => 1,
                'status' => 1,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 1, key: 'data')
            ->assertJsonStructure($jsonStructure);
        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'queryFilter' => 'John Doe1',
                'active' => 0,
                'status' => 0,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 0, key: 'data')
            ->assertJsonStructure($noDataJsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'queryFilter' => 'John Doe1',
                'active' => 0,
                'status' => 0,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 0, key: 'data')
            ->assertJsonStructure($noDataJsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'queryFilter' => 'John Doe5',
                'active' => 1,
                'status' => 1,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 1, key: 'data')
            ->assertJsonStructure($jsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'queryFilter' => 'John Doe5',
                'active' => 0,
                'status' => 0,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 0, key: 'data')
            ->assertJsonStructure($noDataJsonStructure);
        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'active' => 1,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 4, key: 'data')
            ->assertJsonStructure($jsonStructure);

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'hasPosts' => 1,
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 0, key: 'data')
            ->assertJsonStructure($noDataJsonStructure);
        PostModel::factory()
            ->createMany([
                ['name' => 'Post 1', 'user_id' => UserModel::query()->whereEmail('john1@example.com')->first()->id],
                ['name' => 'Post 2', 'user_id' => UserModel::query()->whereEmail('john2@example.com')->first()->id],
            ])->fresh();

        $this->getJson(uri: route('users.index', [
            'filters' => json_encode([
                'hasPosts' => 1,
                'queryFilter' => 'john2@example.com',
            ]),
        ]))
            ->assertOk()
            ->assertJsonCount(count: 1, key: 'data')
            ->assertJsonStructure($jsonStructure);
    });
    it(description: 'can_create_a_user_in_api', closure: function () {
        $user = UserModel::factory()
            ->raw(
                [
                    'status' => 1,
                    'active' => 0,
                ]
            );
        $response = $this->postJson(uri: route('users.store'), data: $user);
        $response->assertStatus(status: 201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'status',
                    'active',
                ],
            ]);
        $this->assertDatabaseHas(table: 'users', data: $user);
    });
    it(description: 'can_update_a_user', closure: function () {
        $user = UserModel::factory()
            ->create(
                [
                    'name' => 'John Doe1',
                    'email' => 'john1@example.com',
                    'password' => 'password123',
                    'status' => 1,
                    'active' => 0,
                ]
            )->fresh();
        $response = $this->putJson(uri: route('users.update', $user->id), data: [
            'name' => 'Jane Doe2',
            'email' => 'jane2@example.com',
            'password' => 'password123',
            'status' => 0,
            'active' => 1,
        ]);
        $response->assertStatus(status: 200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'status',
                    'active',
                    'created_at',
                    'updated_at',
                ],
            ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe2',
            'email' => 'jane2@example.com',
            'status' => 0,
            'active' => 1,
        ]);
    });
    it(description: 'can_delete_a_user', closure: function () {
        $user = UserModel::factory()
            ->create([
                'name' => 'John Doe1',
                'email' => 'john1@example.com',
                'password' => 'password123',
                'status' => 1,
                'active' => 0,
            ])->fresh();
        $response = $this->deleteJson(uri: route('users.destroy', $user->id));
        $response->assertNoContent();
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe1',
            'email' => 'john1@example.com',
            'status' => 1,
            'active' => 0,
            'deleted_at' => now(),
        ]);

        $this->assertSame(0, UserModel::query()
            ->count());
    });
    it(description: 'can_get_a_user', closure: function () {
        $user = UserModel::factory()
            ->create()
            ->fresh();
        $response = $this->get(uri: route('users.show', $user->id));
        $response->assertStatus(status: 200)
            ->assertJsonStructure(
                [
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'active',
                        'created_at',
                        'updated_at',
                    ],
                ]
            );
    });
    it(description: 'can_post_a_user_with_posts_ids', closure: function () {
        $post = PostModel::factory()->raw([
            'name' => 'Post 1',
            'desc' => 'Post 1 desc',
            'status' => 1,
            'active' => 0,
        ]);
        $user = UserModel::factory()
            ->raw([
                'name' => 'user1',
                'email' => 'user1@example.com',
                'password' => 'password123',
                'status' => 1,
                'active' => 0,
            ]);
        $response = $this->postJson(uri: route('users.store'), data: [
            ...$user,
            'post' => $post,
        ]);
        $response->assertStatus(status: 201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'status',
                    'active',
                ],
            ]);
        $this->assertDatabaseHas(table: 'users', data: [
            'name' => 'user1',
            'email' => 'user1@example.com',
            'status' => 1,
            'active' => 0,
        ]);
        $this->assertDatabaseHas(table: 'posts', data: [
            'name' => 'Post 1',
            'desc' => 'Post 1 desc',
            'status' => 1,
            'active' => 0,
            'user_id' => $response->json()['data']['id'],
        ]);
        $this->assertSame(1, UserModel::query()->find($response->json()['data']['id'])->posts()->count());
    });
});
