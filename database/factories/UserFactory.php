<?php

namespace TestMonitor\Searchable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TestMonitor\Searchable\Test\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ];
    }
}
