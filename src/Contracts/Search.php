<?php

namespace TestMonitor\Searchable\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface Search
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<TModelClass> $query
     *
     * @return mixed
     */
    public function __invoke(Builder $query, string $property, string $term);
}
