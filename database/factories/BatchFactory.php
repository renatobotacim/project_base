<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\City;
use App\Models\Owner;
use App\Models\Producer;
use App\Models\Team;
use App\Models\TicketEvent;
use App\Models\User;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $common = new Service();
        $batch = $common->generateHash("B", 14);
        $date = Carbon::now()->addDay(rand(1,30));
        $value = $this->faker->numberBetween(100, 1000);
        return [
            'batch' => $batch,
            'value' => $value,
            'rate' => $value * 0.1,
            'amount' => $this->faker->numberBetween(100, 1000),
            'date_limit' => $date,
            'ticket_event_id' => TicketEvent::all()->random(),
        ];
    }

}
