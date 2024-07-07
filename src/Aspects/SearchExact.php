<?php

namespace TestMonitor\Searchable\Aspects;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Contracts\Search;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements \App\Models\Search\Search<TModelClass>
 */
class SearchExact implements Search
{
    /**
     * @var array
     */
    protected array $relationConstraints = [];

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param string $property
     * @param string $term
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function __invoke(Builder $query, string $property, string $term): void
    {
        if ($this->isRelationProperty($query, $property)) {
            $this->withRelationConstraint($query, $property, $term);

            return;
        }

        $query->where($query->qualifyColumn($property), '=', $term);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $property
     * @return bool
     */
    protected function isRelationProperty(Builder $query, string $property): bool
    {
        if (! Str::contains($property, '.')) {
            return false;
        }

        $firstRelationship = explode('.', $property)[0];

        if (! method_exists($query->getModel(), $firstRelationship)) {
            return false;
        }

        return is_a($query->getModel()->{$firstRelationship}(), Relation::class);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $property
     * @param string $term
     *
     * @throws \RuntimeException
     */
    protected function withRelationConstraint(Builder $query, string $property, string $term): void
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(fn (Collection $parts) => [
                $parts->except(count($parts) - 1)->implode('.'),
                $parts->last(),
            ]);

        $query->whereHas($relation, function (Builder $query) use ($property, $term) {
            $this->relationConstraints[] = $property = $query->qualifyColumn($property);

            $this->__invoke($query, $property, $term);
        });
    }
}
