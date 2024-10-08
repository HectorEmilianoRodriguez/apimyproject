<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idCard' => $this->faker->randomElement([1,5]),
            'idJoinUserWork' => $this->faker->randomElement([1,5]),
            'logicdeleted' => 0,
            'text' => $this->faker->sentence(),
            'seen' => $this->faker->randomElement([0,1]),
        ];
    }
}
