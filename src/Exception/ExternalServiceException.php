<?php

namespace Georgeff\Problem\Exception;

use Throwable;

class ExternalServiceException extends DomainException
{
    public function __construct(
        string $message,
        string $severity = 'error',
        array $context = [],
        ?string $correlationId = null,
        bool $retryable = true,
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
        return 'EXT_' . $this->generateErrorCodeSuffix((int) $this->getCode());
    }
}
