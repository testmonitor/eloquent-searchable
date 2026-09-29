<?php

namespace TestMonitor\Searchable;

use Illuminate\Support\ServiceProvider;
use TestMonitor\Searchable\Requests\SearchRequest;

class SearchableServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/searchable.php' => config_path('searchable.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../config/searchable.php', 'searchable');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->bind(SearchRequest::class, fn ($app) => SearchRequest::fromRequest($app['request']));
    }
}
