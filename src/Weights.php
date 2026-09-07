<?php

namespace TestMonitor\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Weights
{
    /**
     * @var array<string,int>
     */
    protected array $weights = [];

    public function register(Builder $query, int $weight = 1): void
    {
        $sql = $this->compileWheresIntoSQL($query);

        $condition = strstr($sql, ' ');

        $this->weights[$condition] = $weight;
    }

    public function registerIf(bool $condition, Builder $query, int $weight = 1): void
    {
        if ($condition) {
            $this->register($query, $weight);
        }
    }

    /**
     * Compile all where conditions to SQL.
     */
    protected function compileWheresIntoSQL(Builder $query): string
    {
        $grammar = $query->getQuery()->getGrammar();

        return $grammar->substituteBindingsIntoRawSql(
            $grammar->compileWheres($query->getQuery()),
            $query->getQuery()->getBindings()
        );
    }

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

        return $query->orderByDesc(DB::raw("CASE {$cases} ELSE 0 END"));
    }
}
