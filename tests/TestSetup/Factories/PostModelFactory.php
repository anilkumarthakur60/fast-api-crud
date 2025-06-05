<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Factories;

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostModel>
 */
class PostModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PostModel>
     */
    protected $model = PostModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'desc' => $this->faker->text,
            'user_id' => UserModel::factory(),
            'status' => $this->faker->boolean,
            'active' => $this->faker->boolean,
        ];
    }
}
