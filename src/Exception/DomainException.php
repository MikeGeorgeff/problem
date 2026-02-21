<?php

namespace Georgeff\Problem\Exception;

use Throwable;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

abstract class DomainException extends \Exception
{
    private const array SEVERITY_LEVELS = ['critical', 'error', 'warning', 'info', 'debug'];

    public protected(set) ?string $correlationId;

    private string $_severity;

    public protected(set) string $severity {
        get => $this->_severity;

        set(string $value) {
            $value = strtolower($value);

            if (!in_array($value, self::SEVERITY_LEVELS, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid severity level [%s]. Valid Levels: %s',
                    $value,
                    implode(', ', self::SEVERITY_LEVELS)
                ));
            }

            $this->_severity = $value;
        }
    }

    /**
     * @var array<string, mixed>
     */
    public protected(set) array $context = [];

    public protected(set) bool $retryable;

    public protected(set) DateTimeImmutable $occurredAt;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $_metadata = null;

    /**
     * @var array<string, mixed>
     */
    public array $metadata {
        get => $this->_metadata ??= $this->captureMetadata();
    }

    /**
     * @var array<string, mixed>
     */
    public array $structuredData {
        get => [
            'error_code'     => $this->getErrorCode(),
            'message'        => $this->getMessage(),
            'severity'       => $this->severity,
            'correlation_id' => $this->correlationId,
            'retryable'      => $this->retryable,
            'occurred_at'    => $this->occurredAt->format(DateTimeInterface::RFC3339),
            'context'        => $this->context,
            'metadata'       => $this->metadata,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        string $severity,
        array $context = [],
        ?string $correlationId = null,
        bool $retryable = false,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->correlationId = $correlationId;
        $this->severity      = $severity;
        $this->context       = $context;
        $this->retryable     = $retryable;
        $this->occurredAt    = new DateTimeImmutable();
    }

    /**
     * Get the error code
     * Format: PREFIX_NNNN (e.g. DB_0001)
     */
    abstract public function getErrorCode(): string;

    /**
     * @return array<string, mixed>
     */
    protected function captureMetadata(): array
    {
        return [
            'file'        => $this->getFile(),
            'line'        => $this->getLine(),
            'class'       => static::class,
            'php_version' => PHP_VERSION,
        ];
    }

    protected function generateErrorCodeSuffix(int $code): string
    {
        return str_pad((string) $code, 4, '0', STR_PAD_LEFT);
    }
}
