<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;
use TestMonitor\Searchable\Test\Models\User;

class WeightedSearchTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = User::factory()
            ->count(3)
            ->state(new Sequence(
                ['name' => 'Alice D.', 'email' => 'doe@email.com'],
                ['name' => 'Bob Doe', 'email' => 'doe@email.com'],
                ['name' => 'Chris D.', 'email' => 'doe@email.com'],
            ))
            ->create();
    }

    #[Test]
    public function it_will_prioritize_higher_weighted_search_aspects()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'doe'])
        ));

        // When
        $results = User::query()
            ->searchUsing([
                SearchAspect::partial(name: 'name', weight: 5),
                SearchAspect::partial(name: 'email', weight: 1),
            ])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(3, $results);
        $this->assertEquals($results->first()->name, 'Bob Doe');
    }
}
