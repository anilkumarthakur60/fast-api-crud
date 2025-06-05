<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Factories;

use Anil\FastApiCrud\Tests\TestSetup\Models\PermissionModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermissionModel>
 */
class PermissionModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PermissionModel>
     */
    protected $model = PermissionModel::class;

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
