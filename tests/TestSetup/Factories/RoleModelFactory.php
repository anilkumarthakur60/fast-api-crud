<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Factories;

use Anil\FastApiCrud\Tests\TestSetup\Models\RoleModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleModel>
 */
class RoleModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<RoleModel>
     */
    protected $model = RoleModel::class;

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
