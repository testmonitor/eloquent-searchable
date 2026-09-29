<?php

namespace TestMonitor\Searchable\Contracts;

use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Weights;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface Search
{
    /**
     * @param Builder<TModelClass> $query
     */
    public function __invoke(Builder $query, Weights $weights, string $property, string $term, int $weight = 1): void;
}
