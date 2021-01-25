<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Exceptions;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Enumerable;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use Throwable;

final class ExceptionParser
{

    /**
     * @var Pipeline
     */
    private Pipeline $pipeline;

    /**
     * @var Error|null
     */
    private ?Error $default = null;

    /**
     * @var array
     */
    private array $pipes = [
        Pipes\AuthenticationExceptionHandler::class,
        Pipes\HttpExceptionHandler::class,
        Pipes\RequestExceptionHandler::class,
        Pipes\UnexpectedDocumentExceptionHandler::class,
        Pipes\ValidationExceptionHandler::class,
    ];

    /**
     * Get an exception renderer closure.
     *
     * @return Closure
     */
    public static function renderer(): Closure
    {
        return static fn(Throwable $ex, $request) => self::make()
            ->render($request, $ex);
    }

    /**
     * Fluent constructor.
     *
     * @return static
     */
    public static function make(): self
    {
        return new self(new Pipeline(Container::getInstance()));
    }

    /**
     * ExceptionParser constructor.
     *
     * @param Pipeline $pipeline
     */
    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    /**
     * Use the provided pipes to parse exceptions.
     *
     * @param array|null $pipes
     * @return $this
     */
    public function using(?array $pipes): self
    {
        if (is_array($pipes)) {
            $this->pipes = $pipes;
        }

        return $this;
    }

    /**
     * Add pipes before the existing pipes.
     *
     * @param array $pipes
     * @return $this
     */
    public function prepend(array $pipes): self
    {
        $this->pipes = array_merge($pipes, $this->pipes);

        return $this;
    }

    /**
     * Add pipes to the end of the existing pipes.
     *
     * @param array $pipes
     * @return $this
     */
    public function append(array $pipes): self
    {
        $this->pipes = array_merge($this->pipes, $pipes);

        return $this;
    }

    /**
     * Use the provided error as the default error.
     *
     * @param Error|Enumerable|array $error
     * @return $this
     */
    public function withDefault($error): self
    {
        $this->default = Error::cast($error);

        return $this;
    }

    /**
     * Render the exception, if the request wants the JSON API media type.
     *
     * @param Request $request
     * @param Throwable $ex
     * @return Response|mixed|null
     */
    public function render($request, Throwable $ex)
    {
        if ($this->isRenderable($request, $ex)) {
            return $this
                ->parse($request, $ex)
                ->toResponse($request);
        }

        return null;
    }

    /**
     * Does the HTTP request require a JSON API error response?
     *
     * This method determines if we need to render a JSON API error response
     * for the client. We need to do this if the client has requested JSON
     * API via its Accept header.
     *
     * @param Request $request
     * @param Throwable $e
     * @return bool
     */
    public function isRenderable($request, Throwable $e): bool
    {
        if ($e instanceof JsonApiException) {
            return true;
        }

        $acceptable = $request->getAcceptableContentTypes();

        return isset($acceptable[0]) && 'application/vnd.api+json' === $acceptable[0];
    }

    /**
     * Parse the exception to an error response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable $ex
     * @return ErrorResponse
     */
    public function parse($request, Throwable $ex): ErrorResponse
    {
        if ($ex instanceof JsonApiException) {
            return $ex->prepareResponse($request);
        }

        return $this->pipeline
            ->send($ex)
            ->through($this->pipes)
            ->via('handle')
            ->then(fn() => new ErrorResponse($this->getDefaultError()));
    }

    /**
     * Get the default JSON API error.
     *
     * @return Error
     */
    private function getDefaultError(): Error
    {
        if ($this->default) {
            return $this->default;
        }

        return Error::make()
            ->setStatus(500)
            ->setTitle(__(Response::$statusTexts[500]));
    }

}
