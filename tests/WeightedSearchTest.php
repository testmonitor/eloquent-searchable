<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Collection;
use TestMonitor\Searchable\Test\Models\User;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;
use Illuminate\Database\Eloquent\Factories\Sequence;

class WeightedSearchTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $users;

    public function setUp(): void
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
