<?php

namespace TestMonitor\Searchable\Aspects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use TestMonitor\Searchable\Contracts\Search;
use TestMonitor\Searchable\Weights;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements Search<TModelClass>
 */
class SearchJson implements Search
{
    /**
     * @var list<string>
     */
    protected array $relationConstraints = [];

    /**
     * @param Builder<Model> $query
     *
     * @throws InvalidArgumentException
     */
    public function __invoke(Builder $query, Weights $weights, string $property, string $term, int $weight = 1): void
    {
        $term = Str::of($term)->lower()->toString();

        if ($this->isRelationProperty($query, $property)) {
            $this->withRelationConstraint($query, $weights, $property, $term, $weight);

            return;
        }

        $column = $query->getQuery()->getGrammar()->wrap($property);

        // The column is wrapped by the grammar and the search term is bound.
        $query->whereRaw(
            // @phpstan-ignore argument.type
            DB::raw("JSON_SEARCH({$column}, 'one', ?) IS NOT NULL"),
            ["%{$term}%"]
        );

        $weights->registerIf(empty($this->relationConstraints), $query, $weight);
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
