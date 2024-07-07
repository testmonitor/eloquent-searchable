<?php

namespace TestMonitor\Searchable\Aspects;

use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Contracts\Search;

class SearchAspect
{
    /**
     * @param string $name
     * @param \TestMonitor\Searchable\Contracts\Search $searchClass
     */
    public function __construct(protected string $name, protected Search $searchClass)
    {
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     */
    public function search(Builder $query, string $term): void
    {
        ($this->searchClass)($query, $this->name, $term);
    }

    /**
     * @param string $name
     * @return \App\Models\Search\SearchAspect
     */
    public static function exact(string $name): self
    {
        return new self($name, new SearchExact);
    }

    /**
     * @param string $name
     * @return \App\Models\Search\SearchAspect
     */
    public static function partial(string $name): self
    {
        return new self($name, new SearchPartial);
    }

    /**
     * @param string $name
     * @param string $prefix
     * @param bool $exact
     * @return \App\Models\Search\SearchAspect
     */
    public static function prefix(string $name, string $prefix, bool $exact = false): self
    {
        return new self($name, new SearchPrefix($prefix, $exact));
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
}
