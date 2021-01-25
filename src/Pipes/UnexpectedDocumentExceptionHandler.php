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

namespace LaravelJsonApi\Exceptions\Pipes;

use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;
use JsonException;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Spec\UnexpectedDocumentException;
use Throwable;

class UnexpectedDocumentExceptionHandler
{

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * UnexpectedDocumentExceptionHandler constructor.
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
        if ($ex instanceof UnexpectedDocumentException) {
            return new ErrorResponse(
                $this->toError($ex)
            );
        }

        return $next($ex);
    }

    /**
     * @param UnexpectedDocumentException $ex
     * @return Error
     */
    private function toError(UnexpectedDocumentException $ex): Error
    {
        $previous = $ex->getPrevious();

        if ($previous instanceof JsonException) {
            return Error::make()
                ->setStatus(Response::HTTP_BAD_REQUEST)
                ->setCode($previous->getCode())
                ->setTitle($this->translator->get('Invalid JSON'))
                ->setDetail($this->translator->get($previous->getMessage()));
        }

        return Error::make()
            ->setStatus(Response::HTTP_BAD_REQUEST)
            ->setTitle($this->translator->get('Invalid JSON'))
            ->setDetail($this->translator->get($ex->getMessage()));
    }

}
