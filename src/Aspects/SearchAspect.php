<?php

namespace TestMonitor\Searchable\Aspects;

use TestMonitor\Searchable\Weights;
use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Contracts\Search;

class SearchAspect
{
    /**
     * @param string $name
     * @param \TestMonitor\Searchable\Contracts\Search $searchClass
     * @param int $weight
     */
    public function __construct(protected string $name, protected Search $searchClass, protected int $weight = 1)
    {
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \TestMonitor\Searchable\Weights $weights
     * @param string $term
     */
    public function search(Builder $query, Weights $weights, string $term): void
    {
        ($this->searchClass)($query, $weights, $this->name, $term, $this->weight);
    }

    /**
     * @param string $name
     * @param int $weight
     * @return \App\Models\Search\SearchAspect
     */
    public static function exact(string $name, int $weight = 1): self
    {
        return new self($name, new SearchExact, $weight);
    }

    /**
     * @param string $name
     * @param int $weight
     * @return \App\Models\Search\SearchAspect
     */
    public static function partial(string $name, int $weight = 1): self
    {
        return new self($name, new SearchPartial, $weight);
    }

    /**
     * @param string $name
     * @param string $prefix
     * @param bool $exact
     * @param int $weight
     * @return \App\Models\Search\SearchAspect
     */
    public static function prefix(string $name, string $prefix, bool $exact = false, int $weight = 1): self
    {
        return new self($name, new SearchPrefix($prefix, $exact), $weight);
    }

    /**
     * @param string $name
     * @param int $weight
     * @return \App\Models\Search\SearchAspect
     */
    public static function json(string $name, int $weight = 1): self
    {
        return new self($name, new SearchJson, $weight);
    }

    /**
     * @param string $name
     * @param \TestMonitor\Searchable\Contracts\Search $searchClass
     * @return \App\Models\Search\SearchAspect
     */
    public static function custom(string $name, Search $searchClass): self
    {
        return new self($name, $searchClass);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }
}
