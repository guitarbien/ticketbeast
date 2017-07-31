<?php

namespace Tests;

use App\Exceptions\DisableExceptionHandler;
use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\TestResponse;
use PHPUnit\Framework\Assert;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use CreatesApplication;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected function setUp()
    {
        parent::setUp();
        \Mockery::getConfiguration()->allowMockingNonExistentMethods(false);

        TestResponse::macro('data', function($key) {
            return $this->original->getData()[$key];
        });

        TestResponse::macro('assertViewIs', function ($name) {
           Assert::assertEquals($name, $this->original->name());
        });
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new DisableExceptionHandler);
    }

    protected function from($url)
    {
        session()->setPreviousUrl($url);
        return $this;
    }
}
