<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Category;
use App\Models\City;
use App\Models\Owner;
use App\Models\Event;
use App\Models\Producer;
use App\Models\Team;
use App\Models\User;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $common = new Service();
        $event = $common->generateHash("E", 14);
        $name = $this->faker->sentence;
        $slug = $common->slugify($name);

        $date = Carbon::now()->addDay(rand(1,90));
        $scheduling = Carbon::now()->subDay(rand(1,10));

        $emphasis_value = $this->faker->numberBetween(0, 1000);
        $emphasis_rate = (float)($emphasis_value / $common->countDays(date("Y-m-d"), $date)) / 100;

        return [
            'event' => $event,
            'name' => $name,
            'slug' => $slug,
            'local' => $this->faker->name,
            'date' => $date,
            'scheduling' => $scheduling,
            'banner' => $this->faker->imageUrl(1920, 600),
            'courtesies' => $this->faker->numberBetween(0, 10),
            'classification' => $this->faker->numberBetween(0, 18),
            'address_id' => Address::all()->random(),
            'category_id' => Category::all()->random(),
            'producer_id' => Producer::all()->random(),
            'description' => $this->faker->text,
            'emphasis_value' => $emphasis_value,
            'emphasis_rate' => $emphasis_rate
        ];
    }

}
