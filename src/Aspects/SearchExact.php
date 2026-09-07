<?php

namespace TestMonitor\Searchable\Aspects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use TestMonitor\Searchable\Concerns\ExtractsQuotedPhrases;
use TestMonitor\Searchable\Contracts\Search;
use TestMonitor\Searchable\Weights;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements \App\Models\Search\Search<TModelClass>
 */
class SearchExact implements Search
{
    use ExtractsQuotedPhrases;

    protected array $relationConstraints = [];

    /**
     * @param Builder<Model> $query
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke(Builder $query, Weights $weights, string $property, string $term, int $weight = 1): void
    {
        if ($this->isRelationProperty($query, $property)) {
            $this->withRelationConstraint($query, $weights, $property, $term, $weight);

            return;
        }

        $query->where($query->qualifyColumn($property), '=', $this->stripQuotedPhrases($term));

        $weights->registerIf(empty($this->relationConstraints), $query, $weight);
    }

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
     * @throws \RuntimeException
     */
    protected function withRelationConstraint(
        Builder $query,
        Weights $weights,
        string $property,
        string $term,
        int $weight = 1
    ): void {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(fn (Collection $parts) => [
                $parts->except(count($parts) - 1)->implode('.'),
                $parts->last(),
            ]);

        $query->whereHas($relation, function (Builder $query) use ($property, $term, $weight, $weights) {
            $this->relationConstraints[] = $property = $query->qualifyColumn($property);

            $this->__invoke($query, $weights, $property, $term, $weight);
        });
    }
}
