<?php

namespace TestMonitor\Searchable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TestMonitor\Searchable\Test\Models\Ticket;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(nb: 5, asText: true),
            'description' => $this->faker->text(),
        ];
    }
}
