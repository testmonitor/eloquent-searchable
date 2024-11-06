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

class ExactSearchTest extends TestCase
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
    public function it_will_find_records_using_an_exact_match()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'Frank Keulen'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Frank Keulen');
    }

    #[Test]
    public function it_will_find_records_using_an_exact_match_and_quoted_search_term()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => '"Frank Keulen"'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Frank Keulen');
    }

    #[Test]
    public function it_will_find_records_using_an_exact_match_and_multiple_fields()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'thijs@email.com'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name'), SearchAspect::exact('email')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Thijs Kok');
    }

    #[Test]
    public function it_will_find_records_using_an_exact_match_using_a_nested_field()
    {
        // Given
        $ticket = Ticket::first();

        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => $ticket->name])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('tickets.name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, $ticket->user->name);
    }

    #[Test]
    public function it_doesnt_return_records_when_an_exact_match_does_not_exists()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'René Ceelen'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_doesnt_return_records_when_only_a_partial_match_exists()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'Stephan Greutveld'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::exact('name')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);
    }
}
