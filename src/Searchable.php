<?php

namespace TestMonitor\Searchable;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Aspects\SearchAspect;
use TestMonitor\Searchable\Requests\SearchRequest;

trait Searchable
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected Collection $searchAspects;

    /**
     * @var \TestMonitor\Searchable\Requests\SearchRequest
     */
    public SearchRequest $searchRequest;

    /**
     * Provide a model search query scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $aspects
     * @param \Illuminate\Http\Request|null $request
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchUsing(Builder $query, string|array $aspects, ?Request $request = null): Builder
    {
        $aspects = is_array($aspects) ? $aspects : func_get_args();

        $this->searchRequest = $request
            ? SearchRequest::fromRequest($request)
            : app(SearchRequest::class);

        $this->searchAspects = collect($aspects)->map(function ($aspect) {
            if ($aspect instanceof SearchAspect) {
                return $aspect;
            }

            return SearchAspect::exact($aspect);
        });

        if ($this->searchRequest->hasTerm()) {
            $query->where(fn (Builder $query) => $this->addSearchAspectsToQuery($query));
        }

        return $query;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    protected function addSearchAspectsToQuery(Builder $query): void
    {
        $this->searchAspects->each(function (SearchAspect $aspect) use ($query) {
            $query->orWhere(
                fn (Builder $query) => $aspect->search($query, $this->searchRequest->term())
            );
        });
    }
}
