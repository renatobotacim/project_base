<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Producer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $aux = (bool) Producer::first();
        $aux = $aux ? Producer::all()->random() : null;

        return [
            'road' => $this->faker->streetAddress,
            'number' => $this->faker->randomNumber(),
            'district' => $this->faker->streetAddress,
            'complement' => $this->faker->text(100),
            'name' => $this->faker->name,
            'city_id' => City::all()->random(),
            'producer_id' => $aux
        ];
    }

}
