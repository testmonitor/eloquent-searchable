<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Database\Eloquent\Factories\Sequence;
use PHPUnit\Framework\Attributes\Test;
use TestMonitor\Searchable\Test\Models\User;
use TestMonitor\Searchable\Weights;

final class WeightsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        User::factory()
            ->count(3)
            ->state(new Sequence(
                ['name' => 'Thijs Kok', 'email' => 'thijs@email.com'],
                ['name' => 'Frank Keulen', 'email' => 'frank@email.com'],
                ['name' => 'Stephan Grootveld', 'email' => 'stephan@email.com'],
            ))
            ->create();
    }

    #[Test]
    public function it_will_order_records_matching_a_registered_weight_first()
    {
        // Given
        $weights = new Weights;

        // When
        $weights->register(User::query()->where('name', 'Frank Keulen'), 5);

        $results = $weights->applyOrderQuery(User::query())->get();

        // Then
        $this->assertCount(3, $results);
        $this->assertEquals('Frank Keulen', $results->first()->name);
    }

    #[Test]
    public function it_will_order_records_by_the_highest_registered_weight()
    {
        // Given
        $weights = new Weights;

        // When
        $weights->register(User::query()->where('name', 'Frank Keulen'), 1);
        $weights->register(User::query()->where('name', 'Stephan Grootveld'), 5);

        $results = $weights->applyOrderQuery(User::query())->get();

        // Then
        $this->assertCount(3, $results);
        $this->assertEquals(
            ['Stephan Grootveld', 'Frank Keulen'],
            $results->pluck('name')->take(2)->all()
        );
    }

    #[Test]
    public function it_will_not_register_a_weight_when_the_condition_is_false()
    {
        // Given
        $weights = new Weights;

        // When
        $weights->registerIf(false, User::query()->where('name', 'Frank Keulen'), 5);

        $query = $weights->applyOrderQuery(User::query());

        // Then
        $this->assertEmpty($query->getQuery()->orders);
    }

    #[Test]
    public function it_will_not_register_a_weight_for_a_query_without_conditions()
    {
        // Given
        $weights = new Weights;

        // When
        $weights->register(User::query(), 5);

        $query = $weights->applyOrderQuery(User::query());

        // Then
        $this->assertEmpty($query->getQuery()->orders);
    }
}
