<?php

namespace TestMonitor\Searchable\Test;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TestMonitor\Searchable\SearchableServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            SearchableServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'test'),
            'username' => env('DB_USERNAME', 'user'),
            'password' => env('DB_PASSWORD', 'passw0rd'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'TestMonitor\\Searchable\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function setUpDatabase(Application $app)
    {
        $builder = $this->app['db']->connection()->getSchemaBuilder();

        $builder->dropIfExists('tickets');
        $builder->dropIfExists('users');

        $builder->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
        });

        $builder->create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('code');
            $table->string('name');
            $table->text('description');
            $table->json('labels')->nullable();
            $table->unsignedInteger('user_id');
        });
    }
}
