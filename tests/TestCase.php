<?php

namespace Whitecube\Sluggable\Tests;

use File;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory().'/database.sqlite',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase()
    {
        file_put_contents($this->getTempDirectory().'/database.sqlite', null);

        $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('test_model_translatables', function (Blueprint $table) {
            $table->increments('id');
            $table->json('title')->nullable();
            $table->json('name')->nullable();
            $table->json('slug')->nullable();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('test_model_custom_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->json('title')->nullable();
            $table->json('url')->nullable();
        });
    }

    protected function initializeDirectory(string $directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    public function getTempDirectory() : string
    {
        return __DIR__.'/temp';
    }
}
