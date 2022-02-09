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
use Illuminate\Contracts\Translation\Translator;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class HttpExceptionHandler
{

    use Concerns\SetsHttpTitle;

    /**
     * HttpExceptionHandler constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
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
        if ($ex instanceof HttpExceptionInterface) {
            return ErrorResponse::error($this->toError($ex))
                ->withHeaders($ex->getHeaders());
        }

        return $next($ex);
    }

    /**
     * @param HttpExceptionInterface $ex
     * @return Error
     */
    private function toError(HttpExceptionInterface $ex): Error
    {
        return Error::make()
            ->setStatus($status = $ex->getStatusCode())
            ->setTitle($this->getTitle($status))
            ->setDetail($this->translator->get($ex->getMessage()));
    }

}
