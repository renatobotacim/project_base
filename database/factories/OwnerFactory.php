<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\City;
use App\Models\Producer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Owner>
 */
class OwnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'cpf' => $this->faker->numerify('###########'),
            'phone' => $this->faker->numerify('###########'),
            'email' => $this->faker->email,
            'address_id' => Address::all()->random(),
        ];
    }

}
