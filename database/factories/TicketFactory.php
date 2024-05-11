<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Batch;
use App\Models\City;
use App\Models\Owner;
use App\Models\Producer;
use App\Models\Team;
use App\Models\User;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $common = new Service();
        $arrayValues = ['reserved', 'available', 'used', 'won', 'canceled'];
        return [
            'ticket' => $common->generateHash("T", 14),
            'batch_id' => Batch::all()->random(),
            'user_id' => User::all()->random(),
            'status' => $arrayValues[rand(0, 4)],
        ];
    }
}
