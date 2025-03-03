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

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class ExceptionsTest extends TestCase
{

    /**
     * @var Throwable
     */
    private Throwable $ex;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/test', function () {
            throw $this->ex;
        });
    }

    public function testNotFound(): void
    {
        $expected = [
            'errors' => [
                [
                    'detail' => 'The route foobar could not be found.',
                    'status' => '404',
                    'title' => 'Not Found',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/foobar', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testMethodNotAllowed(): void
    {
        $expected = [
            'errors' => [
                [
                    'detail' => 'The POST method is not supported for route test. Supported methods: GET, HEAD.',
                    'status' => '405',
                    'title' => 'Method Not Allowed',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->post('/test', [], ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(405)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * @see https://github.com/laravel-json-api/laravel/issues/21
     */
    public function testMethodNotAllowedHttpException(): void
    {
        $expected = [
            'errors' => [
                [
                    'status' => '405',
                    'title' => 'Method Not Allowed',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->ex = new MethodNotAllowedHttpException(['GET', 'POST']);

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(405)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * A JSON API exception thrown from a non-standard route renders as
     * JSON API.
     */
    public function testJsonApiException(): void
    {
        $this->ex = JsonApiException::error([
            'status' => '418',
            'detail' => "Hello, I'm a teapot.",
        ])->withHeaders(['X-Foo' => 'Bar']);

        $expected = [
            'errors' => [
                [
                    'status' => '418',
                    'detail' => "Hello, I'm a teapot.",
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test')
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertHeader('X-Foo', 'Bar')
            ->assertExactJson($expected);
    }

    public function testMaintenanceMode(): void
    {
        $this->ex = new HttpException(503, 'We are down for maintenance.');

        $expected = [
            'errors' => [
                [
                    'detail' => 'We are down for maintenance.',
                    'title' => 'Service Unavailable',
                    'status' => '503',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(503)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * By default Laravel sends a 419 response for a TokenMismatchException.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/181
     */
    public function testTokenMismatch(): void
    {
        $this->ex = new TokenMismatchException("The token is not valid.");

        $expected = [
            'errors' => [
                [
                    'detail' => 'The token is not valid.',
                    'status' => '419',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(419)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testTokenMismatchWithoutMessage(): void
    {
        $this->ex = new TokenMismatchException();

        $expected = [
            'errors' => [
                [
                    'status' => '419',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(419)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testHttpException(): void
    {
        $this->ex = new HttpException(
            418,
            "I think I might be a teapot.",
            null,
            ['X-Teapot' => 'True']
        );

        $expected = [
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

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(418)
            ->assertHeader('X-Teapot', 'True')
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testHttpExceptionWithoutMessageAndHeaders(): void
    {
        $this->ex = new HttpException(418);

        $expected = [
            'errors' => [
                [
                    'title' => "I'm a teapot",
                    'status' => '418',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testAuthenticationException(): void
    {
        $this->ex = new AuthenticationException('You must sign in.');

        $expected = [
            'errors' => [
                [
                    'detail' => 'You must sign in.',
                    'status' => '401',
                    'title' => 'Unauthorized',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testAuthenticationExceptionWithoutMessage(): void
    {
        $this->ex = new AuthenticationException('Denied');

        $expected = [
            'errors' => [
                [
                    'detail' => 'Denied',
                    'status' => '401',
                    'title' => 'Unauthorized',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testAuthorizationException(): void
    {
        $this->ex = new AuthorizationException('Access denied.');

        $expected = [
            'errors' => [
                [
                    'detail' => 'Access denied.',
                    'status' => '403',
                    'title' => 'Forbidden',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(403)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testAuthorizationExceptionWithoutMessage(): void
    {
        $this->ex = new AuthorizationException('');

        $expected = [
            'errors' => [
                [
                    'status' => '403',
                    'title' => 'Forbidden',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(403)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testRequestException(): void
    {
        $this->ex = new BadRequestException('Your request is badly formatted.');

        $expected = [
            'errors' => [
                [
                    'detail' => 'Bad request.',
                    'status' => '400',
                    'title' => 'Bad Request',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testRequestExceptionWithoutMessage(): void
    {
        $this->ex = new BadRequestException();

        $expected = [
            'errors' => [
                [
                    'detail' => 'Bad request.',
                    'status' => '400',
                    'title' => 'Bad Request',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * If we get a Laravel validation exception we need to convert this to
     * JSON API errors.
     */
    public function testValidationException(): void
    {
        $messages = new MessageBag([
            'email' => 'These credentials do not match our records.',
            'foo.bar' => 'Foo bar is not baz.',
        ]);

        $validator = $this->createMock(Validator::class);
        $validator->method('errors')->willReturn($messages);
        $validator->method('getTranslator')->willReturnCallback(
            fn() => $this->app->make(Translator::class)
        );

        $this->ex = new ValidationException($validator);

        $expected = [
            'errors' => [
                [
                    'detail' => 'These credentials do not match our records.',
                    'source' => ['pointer' => '/email'],
                    'status' => '422',
                    'title' => 'Unprocessable Entity',
                ],
                [
                    'detail' => 'Foo bar is not baz.',
                    'source' => ['pointer' => '/foo/bar'],
                    'status' => '422',
                    'title' => 'Unprocessable Entity',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * If the validator has a status set, we ensure the response and JSON:API errors have that status.
     *
     * @see https://github.com/laravel-json-api/exceptions/issues/3
     */
    public function testValidationExceptionWithStatus(): void
    {
        $this->ex = ValidationException::withMessages([
            'data.email' => 'Hello teapot@example.com',
        ])->status(418);

        $expected = [
            'errors' => [
                [
                    'detail' => 'Hello teapot@example.com',
                    'source' => ['pointer' => '/data/email'],
                    'status' => '418',
                    'title' => "I'm a teapot",
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this
            ->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * If the validator has a status set, we ensure the response and JSON:API errors have that status.
     *
     * @see https://github.com/laravel-json-api/exceptions/issues/3
     */
    public function testValidationExceptionWithStatusThatDoesNotHaveTitle(): void
    {
        $this->ex = ValidationException::withMessages([
            'data.email' => 'Too many attempts',
        ])->status(419);

        $expected = [
            'errors' => [
                [
                    'detail' => 'Too many attempts',
                    'source' => ['pointer' => '/data/email'],
                    'status' => '419',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(419)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testDefaultExceptionWithoutDebug(): void
    {
        config()->set('app.debug', false);

        $this->ex = new \Exception('Boom.');

        $expected = [
            'errors' => [
                [
                    'title' => 'Internal Server Error',
                    'status' => '500',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * @return void
     */
    public function testDefaultExceptionWithDebug(): void
    {
        config()->set('app.debug', true);

        $this->ex = $ex = new \LogicException('Boom.', 99);

        $expected = [
            'errors' => [
                [
                    'code' => (string) $ex->getCode(),
                    'detail' => $ex->getMessage(),
                    'meta' => [
                        'exception' => get_class($ex),
                        'file' => $ex->getFile(),
                        'line' => $ex->getLine(),
                        'trace' => Collection::make($ex->getTrace())
                            ->map(fn($trace) => Arr::except($trace, ['args']))
                            ->all(),
                    ],
                    'status' => '500',
                    'title' => 'Internal Server Error',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this
            ->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * @return void
     */
    public function testDefaultExceptionWithPreviousExceptionAndWithDebug(): void
    {
        config()->set('app.debug', true);

        $this->ex = $ex = new \LogicException(
            message: 'Boom.',
            code: 99,
            previous: $previous1 = new \RuntimeException(
                message: 'Blah!',
                code: 98,
                previous: $previous2 = new \Exception(
                    message: 'Baz!',
                    code: 97
                ),
            ),
        );

        $expected = [
            'errors' => [
                [
                    'code' => (string) $ex->getCode(),
                    'detail' => $ex->getMessage(),
                    'meta' => [
                        'exception' => $ex::class,
                        'file' => $ex->getFile(),
                        'line' => $ex->getLine(),
                        'trace' => Collection::make($ex->getTrace())
                            ->map(fn($trace) => Arr::except($trace, ['args']))
                            ->all(),
                    ],
                    'status' => '500',
                    'title' => 'Internal Server Error',
                ],
                [
                    'code' => (string) $previous1->getCode(),
                    'detail' => $previous1->getMessage(),
                    'meta' => [
                        'exception' => $previous1::class,
                        'file' => $previous1->getFile(),
                        'line' => $previous1->getLine(),
                        'trace' => Collection::make($previous1->getTrace())
                            ->map(fn($trace) => Arr::except($trace, ['args']))
                            ->all(),
                    ],
                    'status' => '500',
                    'title' => 'Internal Server Error',
                ],
                [
                    'code' => (string) $previous2->getCode(),
                    'detail' => $previous2->getMessage(),
                    'meta' => [
                        'exception' => $previous2::class,
                        'file' => $previous2->getFile(),
                        'line' => $previous2->getLine(),
                        'trace' => Collection::make($previous2->getTrace())
                            ->map(fn($trace) => Arr::except($trace, ['args']))
                            ->all(),
                    ],
                    'status' => '500',
                    'title' => 'Internal Server Error',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];

        $this
            ->get('/test', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }
}
