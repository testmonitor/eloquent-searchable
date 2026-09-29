<?php

namespace TestMonitor\Searchable\Aspects;

use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Contracts\Search;
use TestMonitor\Searchable\Weights;

class SearchAspect
{
    public function __construct(
        protected string $name,
        protected Search $searchClass,
        protected int $weight = 1
    ) {
        //
    }

    public function search(Builder $query, Weights $weights, string $term): void
    {
        ($this->searchClass)($query, $weights, $this->name, $term, $this->weight);
    }

    public static function exact(string $name, int $weight = 1): self
    {
        return new self($name, new SearchExact, $weight);
    }

    public static function partial(string $name, int $weight = 1): self
    {
        return new self($name, new SearchPartial, $weight);
    }

    public static function prefix(string $name, string $prefix, bool $exact = false, int $weight = 1): self
    {
        return new self($name, new SearchPrefix($prefix, $exact), $weight);
    }

    public static function json(string $name, int $weight = 1): self
    {
        return new self($name, new SearchJson, $weight);
    }

    public static function custom(string $name, Search $searchClass): self
    {
        return new self($name, $searchClass);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }
}
