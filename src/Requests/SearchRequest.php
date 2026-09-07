<?php

namespace TestMonitor\Searchable\Requests;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SearchRequest extends Request
{
    /**
     * @throws BadRequestException
     * @throws \RuntimeException
     */
    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new self);
    }

    public function hasTerm(): bool
    {
        return strlen($this->term()) >= config('searchable.minimal_length');
    }

    public function term(): string
    {
        return $this->input(config('searchable.parameter')) ?? '';
    }
}
