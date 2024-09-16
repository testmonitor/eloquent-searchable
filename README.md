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
  - [Aspects](#aspects)
  - [Search weighing](#search-weighing)
  - [Search related models](#search-related-models)
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

### Aspects

A model's search strategy is defined by search aspects. An aspect defines a matching configuration. For example,
the exact search aspect perfoms an exact search on the provided attribute.

Eloquent Searcheable provides multiple aspects out of the box. Additionally, you can also define your own
matching configuration using the custom aspect.

#### Exact match

The Exact search aspect will only return matches that are equal to the search term.

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
                SearchAspect::exact(name: 'title'),
            ])
            ->get();
    }
}
```

#### Partial match

The Partial search aspect returns matches where the search query occurs anywhere within the given attribute.

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
                SearchAspect::partial(name: 'title'),
            ])
            ->get();
    }
}
```

#### JSON match

The JSON search aspect returns matches where the search query occurs anywhere within the given JSON attribute.

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
                SearchAspect::json(name: 'settings'),
            ])
            ->get();
    }
}
```

#### Prefix match

The Prefix aspect combines the exact and partial strategy with the ability to strip one or more characters from the search query. 
This can be useful when your application provides an incremental code with a prefix to your user (say, ISSUE-12),
but only store the number in your database (which would be the number 12). 

As a default, the partial match strategy is used. Using the `exact` parameter, you can enable exact matching.

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
                SearchAspect::prefix(name: 'code', prefix: 'ISSUE-', exact: true),
                SearchAspect::prefix(name: 'code', prefix: 'ISSUE-'),
            ])
            ->get();
    }
}
```

#### Custom aspect

You can create your own search aspect by creating a class that implements the `TestMonitor\Searchable\Contracts\Search` contract.

Let's implement a new search aspect: a strategy that matches the beginning of an attribute. Your custom search aspect would look
something like this:

```php
use TestMonitor\Searchable\Weights;
use Illuminate\Database\Eloquent\Builder;
use TestMonitor\Searchable\Contracts\Search;

class StartsWithSearch implements Search
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
        $query->where($property, 'like', "{$term}%");
    }
}
```

To use this custom aspect, define it in your search criteria like this:

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

### Search weighing

Optionally, you can use search weights. Weighing prioritizes search criteria, placing results with higher weights at the top.

Let's make a Post model searchable and use the following criteria:

- A partial keyword match in the post's title should be ranked highest.
- A partial keyword match in the summary should be ranked below any title match.
- A partial keyword match in the description should be ranked below any other criteria.

Here's an example that implements these criteria:

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
                SearchAspect::partial(name: 'title', weight: 20),
                SearchAspect::partial(name: 'summary', weight: 10),
                SearchAspect::partial('description'),
            ])
            ->get();
    }
}
```

### Search related models

Use dotted notation to search through related model attributes.

Let's say you want to search your posts based on their blog's title and description. Here's an example that
implements these criteria:

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
                SearchAspect::exact('blog.title'),
                SearchAspect::partial('blog.description'),
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
