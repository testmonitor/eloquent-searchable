<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Collection;
use TestMonitor\Searchable\Test\Models\User;
use TestMonitor\Searchable\Test\Models\Ticket;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;
use Illuminate\Database\Eloquent\Factories\Sequence;

class PartialSearchTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $users;

    public function setUp(): void
    {
        parent::setUp();

        $this->users = User::factory()
            ->count(4)
            ->state(new Sequence(
                ['name' => 'Thijs Kok', 'email' => 'thijs@email.com'],
                ['name' => 'Frank Keulen', 'email' => 'frank@email.com'],
                ['name' => 'Stephan Grootveld', 'email' => 'stephan@email.com'],
                ['name' => 'Jan Thijssen', 'email' => 'jan@email.com'],
            ))
            ->create();

        $this->users->each(function (User $user) {
            Ticket::factory()
                ->count(3)
                ->state(new Sequence(
                    ['name' => "{$user->name} ticket #1"],
                    ['name' => "{$user->name} ticket #2"],
                    ['name' => "{$user->name} ticket #3"],
                ))
                ->for($user)
                ->create();
        });
    }

    #[Test]
    public function it_will_find_records_using_a_partial_match()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'Thijs'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::partial('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
        $this->assertEquals($results->last()->name, 'Jan Thijssen');
    }

    #[Test]
    public function it_will_find_records_using_a_partial_match_and_quoted_search_term()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => '"Thijs Kok"'])
        ));

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
    public function it_will_find_records_using_a_partial_match_and_multiple_fields()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'stephan@email.com'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::partial('name'), SearchAspect::partial('email')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Stephan Grootveld');
    }

    #[Test]
    public function it_will_find_records_using_a_partial_match_using_a_nested_field()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'Frank'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::partial('tickets.name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Frank Keulen');
    }

    #[Test]
    public function it_doesnt_return_records_when_a_partial_match_does_not_exists()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'René Ceelen'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::partial('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);
    }
}
