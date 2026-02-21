<?php

namespace Georgeff\Problem\Exception;

use Throwable;

class DatabaseException extends DomainException
{
    public function __construct(
        string $message,
        string $severity = 'error',
        array $context = [],
        ?string $correlationId = null,
        bool $retryable = false,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $severity, $context, $correlationId, $retryable, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return 'DB_' . $this->generateErrorCodeSuffix((int) $this->getCode());
    }
}
