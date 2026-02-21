<?php

namespace Georgeff\Problem\Exception;

use Throwable;

/**
 * Unknown Exception Wrapper -- Used in the translator for exceptions that don't match any patterns
 */
final class UnknownException extends DomainException
{
    public function __construct(Throwable $exception, ?string $correlationId = null)
    {
        $context = [
            'original_class' => $exception::class,
            'original_code'  => $exception->getCode(),
            'original_file'  => $exception->getFile(),
            'original_line'  => $exception->getLine(),
        ];

        parent::__construct(
            $exception->getMessage(),
            'error',
            $context,
            $correlationId,
            false,
            $exception->getCode(),
            $exception
        );
    }

    public function getErrorCode(): string
    {
        return 'UNKNOWN_0000';
    }
}
