<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\relcardlabel>
 */
class relcardlabelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $idLabel = $this->faker->randomElement([1,2, 3, 4, 5]);
        $idCard = $this->faker->randomElement([1,2,3,4,5]);
        return [
            'idLabel' => $idLabel,
            'idCard' => $idCard
        ];
    }
}
