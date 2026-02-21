<?php

namespace Georgeff\Problem\Exception;

use Throwable;

class ValidationException extends DomainException
{
    public function __construct(
        string $message,
        string $severity = 'warning',
        array $context = [],
        ?string $correlationId = null,
        bool $retryable = false,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            severity: $severity,
            context: $context,
            correlationId: $correlationId,
            retryable: $retryable,
            code: $code,
            previous: $previous
        );
    }

    public function getErrorCode(): string
    {
        return 'VAL_' . $this->generateErrorCodeSuffix((int) $this->getCode());
    }
}
