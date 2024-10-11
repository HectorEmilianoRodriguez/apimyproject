<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkEnv>
 */
class WorkEnvFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nameW" => $this->faker->name(),
            "type"  => $this->faker->randomElement(['Desarrollo web', 'Redes', 'Arquitectura']),
            "descriptionW" => $this->faker->sentence(), // Asegúrate de que esta columna esté presente en tu migración
            "date_start" => '2024-08-10',
            "date_end" => '2024-09-10',
            'logicdeleted' => 0,
            'codeWork' => Str::random(5)
        ];
    }
    
}
