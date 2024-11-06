<?php

namespace TestMonitor\Searchable\Concerns;

trait ExtractsQuotedPhrases
{
    /**
     * Parses a search term string into individual terms, preserving quoted phrases.
     *
     * @param string $term
     * @return array
     */
    public function extractQuotedPhrases(string $term): array
    {
        return str_getcsv(trim($term), ' ', '"');
    }

    /**
     * Removes quotes from search terms.
     *
     * @param string $term
     * @return string
     */
    public function stripQuotedPhrases(string $term): string
    {
        return trim($term, ' "');
    }
}
