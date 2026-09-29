<?php

namespace TestMonitor\Searchable\Test;

use PHPUnit\Framework\Attributes\Test;
use TestMonitor\Searchable\Aspects\SearchAspect;

final class SearchAspectTest extends TestCase
{
    #[Test]
    public function it_can_retrieve_the_name_of_a_searchaspect()
    {
        // Given
        $aspect = SearchAspect::exact('foobar');

        // When
        $name = $aspect->getName();

        // Then
        $this->assertSame('foobar', $name);
    }

    #[Test]
    public function it_can_retrieve_the_weight_of_a_searchaspect()
    {
        // Given
        $aspect = SearchAspect::exact('foobar', 100);

        // When
        $weight = $aspect->getWeight();

        // Then
        $this->assertSame(100, $weight);
    }
}
