<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Collection;
use TestMonitor\Searchable\Test\Models\User;
use TestMonitor\Searchable\Test\Models\Ticket;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;
use Illuminate\Database\Eloquent\Factories\Sequence;
use TestMonitor\Searchable\Contracts\Search;

class CustomSearchTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $users;

    /**
     * @var \TestMonitor\Searchable\Contracts\Search
     */
    protected $domainSearcher;

    public function setUp(): void
    {
        parent::setUp();

        $this->users = User::factory()
            ->count(3)
            ->state(new Sequence(
                ['name' => 'Thijs Kok', 'email' => 'thijs@email.com'],
                ['name' => 'Frank Keulen', 'email' => 'frank@email.com'],
                ['name' => 'Stephan Grootveld', 'email' => 'stephan@email.com'],
            ))
            ->create();

        $this->domainSearcher = new class() implements Search {
            public function __invoke(Builder $query, string $property, string $term): void
            {
                $query->where($query->qualifyColumn($property), 'LIKE', "%@{$term}");
            }
        };
    }

    #[Test]
    public function it_will_find_records_using_an_exact_match()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'email.com'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::custom('email', new $this->domainSearcher())])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(3, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
    }

    #[Test]
    public function it_doesnt_return_records_when_an_exact_match_does_not_exists()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'mail.com'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::custom('email', new $this->domainSearcher())])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);
    }
}
