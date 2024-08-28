# Eloquent Searchable

[![Latest Stable Version](https://poser.pugx.org/testmonitor/eloquent-searchable/v/stable)](https://packagist.org/packages/testmonitor/eloquent-searchable)
[![codecov](https://codecov.io/gh/testmonitor/eloquent-searchable/graph/badge.svg?token=EK8IWK6R9G)](https://codecov.io/gh/testmonitor/eloquent-searchable)
[![StyleCI](https://styleci.io/repos/824909779/shield)](https://styleci.io/repos/824909779)
[![License](https://poser.pugx.org/testmonitor/eloquent-searchable/license)](https://packagist.org/packages/eloquent-searchable)

A package that provides a search feature for Eloquent models. You can define SearchAspects for your Eloquent model that use different search techniques, such as an exact match or partial match.

It is heavily inspired by Spatie's [Query Builder](https://github.com/spatie/laravel-query-builder/) and can be used in conjunction with this package.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Search weighing](#search-weighing)
  - [Search related models](#search-related-models)
- [Examples](#examples)
  - [Exact match](#exact-match)
  - [Partial match](#partial-match)
  - [Search with prefix](#search-with-prefix)
  - [Custom searcher](#custom-searcher)
- [Tests](#tests)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Installation

This package can be installed through Composer:

```sh
$ composer require testmonitor/eloquent-searchable
```

Next, publish the configuration file:

```sh
$ php artisan vendor:publish --tag=eloquent-searchable
```

The configuration file allows you the change the HTTP parameter name as well
as the minimal query length.

## Usage

To add searchable functionality to your Eloquent model, follow these steps:

1. Use the trait ```TestMonitor\Searchable\Searchable``` in your model(s).
2. Include the searchUsing scope together with one or more SearchAspects.

Add the searchable trait on the models you want to make searchable:

```php
use Illuminate\Database\Eloquent\Model;
use TestMonitor\Searchable\Searchable;

class User extends Model
{
    use Searchable;

    // ...
}
```

Next, include the `searchUsing` scope with `SearchAspects` to define your
search strategy:

```php
use App\Models\User;
use Illuminate\Routing\Controller;
use TestMonitor\Searchable\Aspects\SearchAspect;

class UsersController extends Controller
{
    public function index()
    {
        return User::query()
            ->seachUsing([
                SearchAspect::exact('first_name'),
                SearchAspect::exact('last_name'),
                SearchAspect::partial('email'),
            ])
            ->get();
    }
}
```

In this example, the controller provides a way to search through a
set of users:

- Both the first and last names are searched using the “exact” strategy.
Only exact matches will be returned.
- The email field is searched using the “partial” match strategy. When
the query term occurs within the email address, it will be returned.

The search query is automatically derived from the HTTP request. You can
modify the HTTP query parameter in the configuration file. By default,
the name `query` is used.

### Search weighing
Optionally you can use weights. Weighing prioritizes search criteria, placing results with higher weights at the top.

```php
use App\Models\Post;
use Illuminate\Routing\Controller;
use TestMonitor\Searchable\Aspects\SearchAspect;

class PostsController extends Controller
{
    public function index()
    {
        return Post::query()
            ->seachUsing([
                SearchAspect::partial(name: 'title', weight: 20), // Matching results will be shown at the top.
                SearchAspect::partial(name: 'summary', weight: 10), // Matching results will be shown after weight 20 results.
                SearchAspect::partial('description'), // Searches without weight are at the bottom of the search results.
            ])
            ->get();
    }
}
```

### Search related models
Use dotted notation to search through related model attributes.

```php
use App\Models\Post;
use Illuminate\Routing\Controller;
use TestMonitor\Searchable\Aspects\SearchAspect;

class PostsController extends Controller
{
    public function index()
    {
        return Post::query()
            ->seachUsing([
                SearchAspect::exact('blog.title'),  // Searches the related blog title.
                SearchAspect::partial('blog.description'), // Searches the related blog description.
            ])
            ->get();
    }
}
```

## Examples

### Exact match
Only exact matches will be returned.

```php
use App\Models\Post;
use Illuminate\Routing\Controller;
use TestMonitor\Searchable\Aspects\SearchAspect;

class PostsController extends Controller
{
    public function index()
    {
        return Post::query()
            ->seachUsing([
                SearchAspect::exact(name: 'title', weight: 10),  // Use weights to prioritize search results.
                SearchAspect::exact('description'),
            ])
            ->get();
    }
}
```

### Partial match
When the query term occurs within the given attribute, it will be returned.

```php
use App\Models\Post;
use Illuminate\Routing\Controller;
use TestMonitor\Searchable\Aspects\SearchAspect;

class PostsController extends Controller
{
    public function index()
    {
        return Post::query()
            ->seachUsing([
                SearchAspect::partial(name: 'title', weight: 10),  // Use weights to prioritize search results.
                SearchAspect::partial('description'),
            ])
            ->get();
    }
}
```

### Search with prefix
Search for a result including a prefix. The prefix will be stripped of the search, e.g. `ISSUE-12` will be changed to `12`.

```php
use App\Models\Issue;
use Illuminate\Routing\Controller;
use TestMonitor\Searchable\Aspects\SearchAspect;

class IssuesController extends Controller
{
    public function index()
    {
        return Issue::query()
            ->seachUsing([
                SearchAspect::prefix(name: 'code', prefix: 'ISSUE-', exact: true), // Exact search for ISSUE-QUERY => QUERY
                SearchAspect::prefix(name: 'code', prefix: 'ISSUE-'), // Partial search for ISSUE-QUERY => %QUERY%
            ])
            ->get();
    }
}
```

### Custom searcher
Create your own custom searcher by implementing the `TestMonitor\Searchable\Contracts\Search` contract.

Custom searcher example:
```php
use TestMonitor\Searchable\Weights;
use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Contracts\Search;

class CustomSearch implements Search
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param \TestMonitor\Searchable\Weights $weights
     * @param string $property
     * @param string $term
     * @param int $weight
     */
    public function __invoke(Builder $query, Weights $weights, string $property, string $term, int $weight = 1): void
    {
        $query->where($property, $term); // Custom search functionality goes here.
    }
}
```

Implementation of the custom searcher:
```php
use App\Models\Post;
use Illuminate\Routing\Controller;
use App\Search\Aspects\CustomSearch;
use TestMonitor\Searchable\Aspects\SearchAspect;

class PostsController extends Controller
{
    public function index()
    {
        return Post::query()
            ->seachUsing([
                SearchAspect::custom('name', new CustomSearch),
            ])
            ->get();
    }
}
```

## Tests

The package contains integration tests. You can run them using PHPUnit.

```
$ vendor/bin/phpunit
```

## Changelog

Refer to [CHANGELOG](CHANGELOG.md) for more information.

## Contributing

Refer to [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

## Credits

- [Thijs Kok](https://www.testmonitor.com/)
- [Stephan Grootveld](https://www.testmonitor.com/)
- [Frank Keulen](https://www.testmonitor.com/)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Refer to the [License](LICENSE.md) for more information.
