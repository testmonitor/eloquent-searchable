<?php

namespace TestMonitor\Searchable\Contracts;

use TestMonitor\Searchable\Weights;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface Search
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<TModelClass> $query
     * @param \TestMonitor\Searchable\Weights $weights
     * @param string $property
     * @param string $term
     * @param int $weight
     * @return mixed
     */
    public function __invoke(Builder $query, Weights $weights, string $property, string $term, int $weight = 1): void;
}
