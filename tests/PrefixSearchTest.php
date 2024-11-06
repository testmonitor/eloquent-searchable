<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Collection;
use TestMonitor\Searchable\Test\Models\User;
use TestMonitor\Searchable\Test\Models\Ticket;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;

class PrefixSearchTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $users;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        Ticket::factory()->count(15)->for($user)->create();
    }

    #[Test]
    public function it_will_find_records_using_a_prefixed_match()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'T-11'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::prefix('code', 'T-', true)])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->code, 'T-11');
    }

    #[Test]
    public function it_will_find_records_using_a_prefixed_match_without_using_the_prefix_in_the_query()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => '11'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::prefix('code', 'T-')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->code, 'T-11');
    }

    #[Test]
    public function it_will_find_records_using_a_prefixed_match_and_quoted_search_term()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => '"T-11"'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::prefix('code', 'T-', true)])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->code, 'T-11');
    }

    #[Test]
    public function it_will_find_records_using_a_prefixed_match_using_a_nested_field()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'T-10'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::prefix('tickets.code', 'T-')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_doesnt_return_records_when_a_match_does_not_exists()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'T-42'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::prefix('code', 'T-')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);
    }
}
