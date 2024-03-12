<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Exceptions\Tests\Integration;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Exceptions\ExceptionParser;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AcceptHeaderTest extends TestCase
{

    /**
     * @var array
     */
    private array $jsonApi;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->get('/test', function () {
            throw new HttpException(
                418,
                'I think I might be a teapot.',
            );
        });

        $this->jsonApi = [
            'errors' => [
                [
                    'title' => "I'm a teapot",
                    'detail' => 'I think I might be a teapot.',
                    'status' => '418',
                ]
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Handler::$testRenderer = null;
    }

    /**
     * @inheritDoc
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton(ExceptionHandler::class, Handler::class);
    }

    public function testJsonApi(): void
    {
        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    /**
     * We expect the standard Laravel error JSON response here.
     */
    public function testJson(): void
    {
        $expected = [
            'message' => 'I think I might be a teapot.',
        ];

        $this->get('/test', ['Accept' => 'application/json'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson($expected);
    }

    /**
     * Allow a developer to set the exception parser to render JSON:API even if it is
     * a JSON request.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/582
     */
    public function testAcceptsJson(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsJson()
            ->renderable();

        $this->get('/test', ['Accept' => 'application/json'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    /**
     * Test the `acceptsAll()` helper method, which ensures exceptions
     * are always rendered as JSON:API.
     */
    public function testAcceptsAll(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsAll()
            ->renderable();

        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    /**
     * Test the `acceptsMiddleware()` helper method. This should render JSON:API
     * errors if any of the provided middleware matches.
     */
    public function testAcceptsMiddlewareMatches(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsMiddleware('foo', 'api')
            ->renderable();

        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    public function testAcceptsMiddlewareDoesNotMatch(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsMiddleware('foo', 'bar')
            ->renderable();

        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('teapot');
    }

    /**
     * @see https://github.com/laravel-json-api/laravel/issues/204
     */
    public function testAcceptsMiddlewareWhenRouteNotFound(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsMiddleware('foo', 'api')
            ->renderable();

        $response = $this->get('/blah', ['Accept' => '*/*']);
        $response->assertStatus(404)->assertSee('Not Found');

        // @TODO remove once Laravel 8 is no longer supported (8 doesn't add the header)
        if (version_compare(Application::VERSION, '9.0.0') >= 0) {
            $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    public function testAcceptsMiddlewareWhenRouteNotFoundWithJsonApiMediaType(): void
    {
        $expected = [
            'errors' => [
                [
                    // @TODO this detail has been added at some point in Laravel 9.
//                    'detail' => 'The route blah could not be found.',
                    'title' => 'Not Found',
                    'status' => '404',
                ]
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsMiddleware('foo', 'api')
            ->renderable();

        $this->get('/blah', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJson($expected); // @TODO put back to `assertExactJson` when min version is Laravel 9
    }

    /**
     * Allow a developer to use their own callback for whether JSON:API should be rendered.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/582
     */
    public function testAcceptTrue(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->accept(fn(\Throwable $ex, Request $request) => true)
            ->renderable();

        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    public function testAcceptFalse(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->accept(fn(\Throwable $ex, Request $request) => false)
            ->renderable();

        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('teapot');
    }

    public function testAcceptFalseWithJsonApiAcceptHeader(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->accept(fn(\Throwable $ex, $request) => false)
            ->renderable();

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    /**
     * We should be able to accept multiple accept callbacks. For example,
     * if we wanted to render JSON:API errors if the client accepts JSON
     * or if a particular middleware matches.
     */
    public function testMultipleAcceptCallbacks1(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsJson()
            ->acceptsMiddleware('foo', 'bar')
            ->renderable();

        $this->get('/test', ['Accept' => 'application/json'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    /**
     * We should be able to accept multiple accept callbacks. For example,
     * if we wanted to render JSON:API errors if the client accepts JSON
     * or if a particular middleware matches.
     */
    public function testMultipleAcceptCallbacks2(): void
    {
        Handler::$testRenderer = ExceptionParser::make()
            ->acceptsMiddleware('api')
            ->acceptsJson()
            ->renderable();

        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($this->jsonApi);
    }

    public function testHtml(): void
    {
        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('teapot');
    }
}
