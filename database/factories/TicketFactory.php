<?php

namespace TestMonitor\Searchable\Database\Factories;

use TestMonitor\Searchable\Test\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(nb: 5, asText: true),
            'description' => $this->faker->text(),
            'settings' => json_encode(['key' => $this->faker->uuid]),
        ];
    }
}
