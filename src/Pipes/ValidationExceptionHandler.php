<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace LaravelJsonApi\Exceptions\Pipes;

use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Contracts\ErrorProvider;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Exceptions\Pipes\Concerns\SetsHttpTitle;
use LaravelJsonApi\Validation\Factory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidationExceptionHandler
{
    use SetsHttpTitle;

    /**
     * ValidationExceptionHandler constructor.
     *
     * @param Factory $factory
     * @param Translator|null $translator
     * @TODO next major version, make translator compulsory.
     */
    public function __construct(
        private readonly Factory $factory,
        Translator $translator = null,
    ) {
        $this->translator = $translator;
    }

    /**
     * Handle the exception.
     *
     * @param Throwable $ex
     * @param Closure $next
     * @return ErrorResponse
     */
    public function handle(Throwable $ex, Closure $next): ErrorResponse
    {
        if ($ex instanceof ValidationException) {
            return new ErrorResponse(
                $this->toErrors($ex),
            );
        }

        return $next($ex);
    }

    /**
     * @param ValidationException $ex
     * @return ErrorProvider|ErrorList
     */
    private function toErrors(ValidationException $ex): ErrorProvider|ErrorList
    {
        $errors = $this->factory->createErrors(
            $ex->validator
        );

        if (Response::HTTP_UNPROCESSABLE_ENTITY !== $ex->status) {
            $errors = $errors->toErrors();
            $this->withStatus($ex->status, $errors);
        }

        return $errors;
    }

    /**
     * Override the status and title of the provided error list.
     *
     * As the validation exception can have a custom HTTP status, we sometimes need to override
     * the HTTP status and title on each JSON:API error.
     *
     * This could be improved by allowing the status and title to be overridden on the
     * `ValidatorErrorIterator` class (in the validation package).
     *
     * @param int $status
     * @param ErrorList $errors
     * @return void
     */
    private function withStatus(int $status, ErrorList $errors): void
    {
        $title = $this->getTitle($status);

        /** @var Error $error */
        foreach ($errors as $error) {
            /** This works as error is mutable; note a future version might make error immutable. */
            $error->setStatus($status)->setTitle($title);
        }
    }
}
