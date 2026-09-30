<?php

namespace TestMonitor\Searchable\Aspects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use TestMonitor\Searchable\Concerns\ExtractsQuotedPhrases;
use TestMonitor\Searchable\Contracts\Search;
use TestMonitor\Searchable\Weights;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements Search<TModelClass>
 */
class SearchPrefix implements Search
{
    use ExtractsQuotedPhrases;

    /**
     * @var list<string>
     */
    protected array $relationConstraints = [];

    public function __construct(protected string $prefix, protected bool $exact = false)
    {
        //
    }

    /**
     * @param Builder<Model> $query
     *
     * @throws InvalidArgumentException
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
     *
     * @param Builder<Model> $query
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
     *
     * @param Builder<Model> $query
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
        return preg_replace('/^' . preg_quote($this->prefix, '/') . '/i', '', $term) ?? $term;
    }

    /**
     * @param Builder<Model> $query
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
     * @param Builder<Model> $query
     *
     * @throws RuntimeException
     */
    protected function withRelationConstraint(
        Builder $query,
        Weights $weights,
        string $property,
        string $term,
        int $weight = 1
    ): void {
        $relation = Str::beforeLast($property, '.');
        $column = Str::afterLast($property, '.');

        $query->whereHas($relation, function (Builder $query) use ($column, $term, $weight, $weights) {
            $this->relationConstraints[] = $qualified = $query->qualifyColumn($column);

            $this->__invoke($query, $weights, $qualified, $term, $weight);
        });
    }
}
