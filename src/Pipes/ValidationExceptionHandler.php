<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Contracts\ErrorProvider;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Validation\Factory;
use Throwable;

class ValidationExceptionHandler
{

    /**
     * @var Factory
     */
    private Factory $factory;

    /**
     * ValidationExceptionHandler constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
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
                $this->toErrors($ex)
            );
        }

        return $next($ex);
    }

    /**
     * @param ValidationException $ex
     * @return ErrorProvider
     */
    private function toErrors(ValidationException $ex): ErrorProvider
    {
        return $this->factory->createErrors(
            $ex->validator
        );
    }
}
