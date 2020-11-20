<?php

declare(strict_types=1);

namespace LaravelJsonApi\Exceptions\Tests\Integration;

use Illuminate\Contracts\Debug\ExceptionHandler;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use LaravelJsonApi\Testing\TestExceptionHandler;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    use MakesJsonApiRequests;

    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app)
    {
        return [
            \LaravelJsonApi\Validation\ServiceProvider::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton(ExceptionHandler::class, TestExceptionHandler::class);
    }
}
