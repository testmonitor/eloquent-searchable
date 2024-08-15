<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Collection;
use TestMonitor\Searchable\Test\Models\User;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;
use Illuminate\Database\Eloquent\Factories\Sequence;

class SearchQueryTest extends TestCase
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
                ['name' => 'Thijs Kok', 'email' => 'thijs@email.com'],
                ['name' => 'Frank Keulen', 'email' => 'frank@email.com'],
                ['name' => 'Stephan Grootveld', 'email' => 'stephan@email.com'],
            ))
            ->create();
    }

    #[Test]
    public function it_will_search_through_records_using_a_custom_query_parameter()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['seek' => 'Thijs'])
        ));

        config(['searchable.parameter' => 'seek']);

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::partial('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
    }

    #[Test]
    public function it_will_skip_searching_through_records_when_the_query_parameter_is_too_short()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'S'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(3, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
    }

    #[Test]
    public function it_will_skip_searching_through_records_when_the_query_parameter_is_missing()
    {
        // Given

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(3, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
    }

    #[Test]
    public function it_will_convert_a_request_into_a_searchrequest()
    {
        // Given
        $this->app->bind(Request::class, fn () => new Request(['query' => 'Thijs']));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(3, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
    }

    #[Test]
    public function it_will_fallback_on_exact_search_when_searchaspect_is_avoided()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'Thijs Kok'])
        ));

        // When
        $results = User::query()
            ->searchUsing(['name'])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
    }
}
