# Eloquent Searchable

[![Latest Stable Version](https://poser.pugx.org/testmonitor/eloquent-searchable/v/stable)](https://packagist.org/packages/testmonitor/eloquent-searchable)
[![Travis Build](https://travis-ci.org/testmonitor/eloquent-searchable.svg?branch=main)](https://app.travis-ci.com/github/testmonitor/eloquent-searchable)
[![Code Quality](https://scrutinizer-ci.com/g/testmonitor/eloquent-searchable/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/testmonitor/eloquent-searchable/?branch=main)
[![StyleCI](https://styleci.io/repos/89586066/shield)](https://styleci.io/repos/89586066)
[![License](https://poser.pugx.org/testmonitor/eloquent-searchable/license)](https://packagist.org/packages/eloquent-searchable)

A package that provides a search feature for Eloquent models. You can define SearchAspects for your Eloquent model that use different search techniques, such as an exact match or partial match.

It is heavily inspired by Spatie's [Query Builder](https://github.com/spatie/laravel-query-builder/) and can be used in conjunction with this package.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Examples](#examples)
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

## Examples

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
