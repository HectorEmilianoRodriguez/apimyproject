<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CardUsers>
 */
class CardUsersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
     // Asegurarse de que idWorkEnv e idUser no sean iguales
     
    public function definition(): array
    {

        $idcard = $this->faker->randomElement([1,5]);
        $idjoinuserwork = $this->faker->randomElement([1,5]);
     
        while ($idcard == $idjoinuserwork) {
            $idcard = $this->faker->randomElement([1, 5]);
        }

    
        return [
            'idCard' => $idcard,
            'idJoinUserWork' => $idjoinuserwork
        ];
    }
}
