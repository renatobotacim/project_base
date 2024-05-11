<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\City;
use App\Models\Maps;
use App\Models\Owner;
use App\Models\Producer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Maps>
 */
class SectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aux = [];

        $y = rand(3, 400);

        for ($x = 1; $x < $y; $x++) {
            $aux[] = rand(1, 400);
        }

        return [
            'name' => $this->faker->name,
            'color' => $this->faker->hexColor,
            'points' => $aux,
            'maps_id' => Maps::all()->random(),
        ];
    }

}
