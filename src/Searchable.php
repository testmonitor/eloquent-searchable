<?php

namespace TestMonitor\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;

trait Searchable
{
    protected Collection $searchAspects;

    public SearchRequest $searchRequest;

    protected Weights $searchWeights;

    /**
     * Provide a model search query scope.
     */
    public function scopeSearchUsing(Builder $query, string|array $aspects, ?Request $request = null): Builder
    {
        $aspects = is_array($aspects) ? $aspects : func_get_args();

        $this->searchRequest = $request
            ? SearchRequest::fromRequest($request)
            : app(SearchRequest::class);

        if (! $this->searchRequest->hasTerm()) {
            return $query;
        }

        $this->searchAspects = collect($aspects)->map(function ($aspect) {
            if ($aspect instanceof SearchAspect) {
                return $aspect;
            }

            return SearchAspect::exact($aspect);
        });

        $this->searchWeights = new Weights;

        $query->where(fn (Builder $query) => $this->addSearchAspectsToQuery($query))
            ->tap(fn (Builder $query) => $this->addOrderByWeightToQuery($query));

        return $query;
    }

    protected function addSearchAspectsToQuery(Builder $query): void
    {
        $this->searchAspects->each(function (SearchAspect $aspect) use ($query) {
            $query->orWhere(
                fn (Builder $query) => $aspect->search($query, $this->searchWeights, $this->searchRequest->term())
            );
        });
    }

    protected function addOrderByWeightToQuery(Builder $query): void
    {
        $this->searchWeights->applyOrderQuery($query);
    }
}
