<?php

namespace TestMonitor\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Weights
{
    /**
     * @var array<string,int>
     */
    protected array $weights = [];

    /**
     * @param Builder<Model> $query
     */
    public function register(Builder $query, int $weight = 1): void
    {
        $sql = $this->compileWheresIntoSQL($query);

        $condition = strstr($sql, ' ');

        if ($condition === false) {
            return;
        }

        $this->weights[$condition] = $weight;
    }

    /**
     * @param Builder<Model> $query
     */
    public function registerIf(bool $condition, Builder $query, int $weight = 1): void
    {
        if ($condition) {
            $this->register($query, $weight);
        }
    }

    /**
     * Compile all where conditions to SQL.
     *
     * @param Builder<Model> $query
     */
    protected function compileWheresIntoSQL(Builder $query): string
    {
        $grammar = $query->getQuery()->getGrammar();

        return $grammar->substituteBindingsIntoRawSql(
            $grammar->compileWheres($query->getQuery()),
            $query->getQuery()->getBindings()
        );
    }

    /**
     * @template TModel of Model
     *
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function applyOrderQuery(Builder $query): Builder
    {
        if (empty($this->weights)) {
            return $query;
        }

        $conditions = array_map(
            fn ($condition, $weight) => "WHEN {$condition} THEN {$weight}",
            array_keys($this->weights),
            $this->weights
        );

        $cases = implode(' ', $conditions);

        // Conditions are SQL compiled by the query grammar, with bindings escaped by the connection.
        // @phpstan-ignore argument.type
        return $query->orderByDesc(DB::raw("CASE {$cases} ELSE 0 END"));
    }
}
