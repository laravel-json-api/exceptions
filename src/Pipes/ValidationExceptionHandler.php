<?php

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
