<?php

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
