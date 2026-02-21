<?php

namespace Georgeff\Problem;

use Throwable;
use LogicException;
use InvalidArgumentException;
use Georgeff\Problem\Exception\DomainException;

class ExceptionBuilder
{
    private string $message = '';

    private string $severity = 'error';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    private bool $retryable = false;

    private int $code = 0;

    private ?Throwable $previous = null;

    private ?string $correlationId = null;

    final public function __construct(private readonly string $exceptionClass)
    {
        if (!is_subclass_of($exceptionClass, DomainException::class)) {
            throw new InvalidArgumentException("Exception class [{$exceptionClass}] must extend " . DomainException::class);
        }
    }

    public static function exception(string $exceptionClass): static
    {
        return new static($exceptionClass);
    }

    protected function hasMessage(): bool
    {
        return '' !== $this->message;
    }

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function debug(): static
    {
        $this->severity = __FUNCTION__;

        return $this;
    }

    public function info(): static
    {
        $this->severity = __FUNCTION__;

        return $this;
    }

    public function warning(): static
    {
        $this->severity = __FUNCTION__;

        return $this;
    }

    public function error(): static
    {
        $this->severity = __FUNCTION__;

        return $this;
    }

    public function critical(): static
    {
        $this->severity = __FUNCTION__;

        return $this;
    }

    /**
     * Merge context
     *
     * @param array<string, mixed> $context
     */
    public function context(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Add a single item to the context
     */
    public function with(string $key, mixed $value): static
    {
        $this->context[$key] = $value;

        return $this;
    }

    public function correlationId(?string $id): static
    {
        $this->correlationId = $id;

        return $this;
    }

    public function retryable(): static
    {
        $this->retryable = true;

        return $this;
    }

    public function notRetryable(): static
    {
        $this->retryable = false;

        return $this;
    }

    public function code(int $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function previous(Throwable $previous): static
    {
        $this->previous = $previous;

        return $this;
    }

    public function build(): DomainException
    {
        if ('' === $this->message) {
            throw new LogicException('Exception message is required');
        }

        /** @var DomainException $e **/
        $e = new ($this->exceptionClass)(
            message: $this->message,
            severity: $this->severity,
            context: $this->context,
            retryable: $this->retryable,
            correlationId: $this->correlationId,
            code: $this->code,
            previous: $this->previous
        );

        return $e;
    }

    /**
     * @throws DomainException
     */
    public function throw(): never
    {
        throw $this->build();
    }
}
