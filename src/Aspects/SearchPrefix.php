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
class SearchPrefix implements Search
{
    use ExtractsQuotedPhrases;

    protected array $relationConstraints = [];

    public function __construct(protected string $prefix, protected bool $exact = false)
    {
        //
    }

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

        $query->when(
            $this->exact,
            fn (Builder $query) => $this->searchForExactMatch($query, $property, $term),
            fn (Builder $query) => $this->searchForPartialMatch($query, $property, $term)
        );

        $weights->registerIf(empty($this->relationConstraints), $query, $weight);
    }

    /**
     * Search for an exact match.
     */
    protected function searchForExactMatch(Builder $query, string $property, string $term): void
    {
        $unquoted = $this->stripQuotedPhrases($term);

        $query->where(
            $query->qualifyColumn($property),
            '=',
            $this->stripPrefix($unquoted)
        );
    }

    /**
     * Search for a partial match.
     */
    protected function searchForPartialMatch(Builder $query, string $property, string $term): void
    {
        foreach ($this->extractQuotedPhrases($term) as $term) {
            $query->where($query->qualifyColumn($property), 'LIKE', $this->stripPrefix($term) . '%');
        }
    }

    /**
     * Strip defined prefix from a search term.
     */
    protected function stripPrefix(string $term): string
    {
        return preg_replace('/^' . preg_quote($this->prefix, '/') . '/i', '', $term);
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
