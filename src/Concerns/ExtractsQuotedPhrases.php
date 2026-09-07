<?php

namespace TestMonitor\Searchable\Concerns;

trait ExtractsQuotedPhrases
{
    /**
     * Parses a search term string into individual terms, preserving quoted phrases.
     */
    public function extractQuotedPhrases(string $term): array
    {
        return str_getcsv(trim($term), ' ', '"');
    }

    /**
     * Removes quotes from search terms.
     */
    public function stripQuotedPhrases(string $term): string
    {
        return trim($term, ' "');
    }
}
