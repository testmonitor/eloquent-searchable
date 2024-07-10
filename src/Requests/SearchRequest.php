<?php

namespace TestMonitor\Searchable\Requests;

use Illuminate\Http\Request;

class SearchRequest extends Request
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\BadRequestException
     * @throws \RuntimeException
     *
     * @return \TestMonitor\Searchable\Requests\SearchRequest
     */
    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new self);
    }

    /**
     * @return bool
     */
    public function hasTerm(): bool
    {
        return strlen($this->term()) >= config('searchable.minimal_length');
    }

    /**
     * @return string
     */
    public function term(): string
    {
        return $this->input(config('searchable.parameter')) ?? '';
    }
}
