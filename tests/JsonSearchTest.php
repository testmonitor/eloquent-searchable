<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;
use TestMonitor\Searchable\Test\Models\Ticket;
use TestMonitor\Searchable\Test\Models\User;

final class JsonSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $users = User::factory()
            ->count(3)
            ->state(new Sequence(
                ['name' => 'Thijs Kok', 'email' => 'thijs@email.com'],
                ['name' => 'Frank Keulen', 'email' => 'frank@email.com'],
                ['name' => 'Stephan Grootveld', 'email' => 'stephan@email.com'],
            ))
            ->create();

        $labels = [
            'Thijs Kok' => ['bug', 'urgent'],
            'Frank Keulen' => ['feature'],
            'Stephan Grootveld' => ['bug', "won't fix"],
        ];

        $users->each(function (User $user) use ($labels) {
            Ticket::factory()
                ->for($user)
                ->create(['name' => "{$user->name} ticket", 'labels' => $labels[$user->name]]);
        });
    }

    #[Test]
    public function it_will_find_records_using_a_json_match()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'urgent'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::json('labels')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals('Thijs Kok ticket', $results->first()->name);
    }

    #[Test]
    public function it_will_find_records_using_a_json_match_on_part_of_a_value()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'feat'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::json('labels')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals('Frank Keulen ticket', $results->first()->name);
    }

    #[Test]
    public function it_will_find_records_using_a_json_match_and_a_search_term_with_quotes()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => "won't"])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::json('labels')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals('Stephan Grootveld ticket', $results->first()->name);
    }

    #[Test]
    public function it_will_find_records_using_a_json_match_using_a_nested_field()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'bug'])
        ));

        // When
        $results = User::query()
            ->searchUsing([SearchAspect::json('tickets.labels')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);
        $this->assertEquals('Thijs Kok', $results->first()->name);
        $this->assertEquals('Stephan Grootveld', $results->last()->name);
    }

    #[Test]
    public function it_will_find_records_using_a_json_match_using_a_qualified_column()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'feature'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::json('tickets.labels')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals('Frank Keulen ticket', $results->first()->name);
    }

    #[Test]
    public function it_doesnt_return_records_when_a_json_match_does_not_exists()
    {
        // Given
        $this->app->bind(SearchRequest::class, fn () => SearchRequest::fromRequest(
            new Request(['query' => 'René Ceelen'])
        ));

        // When
        $results = Ticket::query()
            ->searchUsing([SearchAspect::json('labels')])
            ->get();

        // Then
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);
    }
}
