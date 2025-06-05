<?php

namespace Anil\FastApiCrud\Database\Factories;

use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TagModel>
 */
class TagModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TagModel>
     */
    protected $model = TagModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'desc' => $this->faker->sentence(),
            'status' => $this->faker->boolean(),
            'active' => $this->faker->boolean(),
        ];
    }
}
