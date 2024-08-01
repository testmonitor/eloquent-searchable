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

class JsonSearchTest extends TestCase
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
                ['name' => 'Thijs Kok', 'email' => 'thijs@email.com', 'settings' => json_encode(['foo' => 'bar'])],
                ['name' => 'Frank Keulen', 'email' => 'frank@email.com', 'settings' => json_encode(['foo' => 'fighters'])],
                ['name' => 'Stephan Grootveld', 'email' => 'stephan@email.com', 'settings' => json_encode(['foo' => 'far'])],
            ))
            ->create();

        $this->users->each(function (User $user) {
            Ticket::factory()
                ->count(3)
                ->state(new Sequence(
                    ['name' => "{$user->name} ticket #1", 'settings' => json_encode(['foo' => 'a ticket to ride'])],
                    ['name' => "{$user->name} ticket #2", 'settings' => json_encode(['foo' => 'tickets everywhere'])],
                    ['name' => "{$user->name} ticket #3", 'settings' => json_encode(['foo' => 'no tickets'])],
                ))
                ->for($user)
                ->create();
        });
    }

    #[Test]
    public function it_will_find_records_using_a_json_search()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'fighters'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::json('settings')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals($results->first()->name, 'Frank Keulen');
    }

    #[Test]
    public function it_will_find_records_using_an_json_search_using_a_nested_field()
    {
        // Given
        $ticket = Ticket::first();

        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'a ticket to ride'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::json('tickets.settings')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(3, $results);
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
            ->searchUsing([SearchAspect::json('settings')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);
    }
}
