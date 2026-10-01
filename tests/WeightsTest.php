<?php

namespace TestMonitor\Searchable\Test;

use PHPUnit\Framework\Attributes\Test;
use TestMonitor\Searchable\Test\Models\User;
use TestMonitor\Searchable\Weights;

final class WeightsTest extends TestCase
{
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
