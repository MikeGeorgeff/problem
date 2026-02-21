<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\UnknownException;
use RuntimeException;

class UnknownExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new UnknownException(new RuntimeException('Err')));
    }

    public function test_wraps_message_from_original_exception(): void
    {
        $original  = new RuntimeException('Original message');
        $exception = new UnknownException($original);

        $this->assertSame('Original message', $exception->getMessage());
    }

    public function test_wraps_code_from_original_exception(): void
    {
        $original  = new RuntimeException('Error', 42);
        $exception = new UnknownException($original);

        $this->assertSame(42, $exception->getCode());
    }

    public function test_original_exception_is_set_as_previous(): void
    {
        $original  = new RuntimeException('Error');
        $exception = new UnknownException($original);

        $this->assertSame($original, $exception->getPrevious());
    }

    public function test_severity_is_error(): void
    {
        $exception = new UnknownException(new RuntimeException('Error'));

        $this->assertSame('error', $exception->severity);
    }

    public function test_is_not_retryable(): void
    {
        $exception = new UnknownException(new RuntimeException('Error'));

        $this->assertFalse($exception->retryable);
    }

    public function test_error_code_is_unknown_0000(): void
    {
        $exception = new UnknownException(new RuntimeException('Error'));

        $this->assertSame('UNKNOWN_0000', $exception->getErrorCode());
    }

    public function test_context_captures_original_exception_class(): void
    {
        $original  = new RuntimeException('Error');
        $exception = new UnknownException($original);

        $this->assertSame(RuntimeException::class, $exception->context['original_class']);
    }

    public function test_context_captures_original_code(): void
    {
        $original  = new RuntimeException('Error', 99);
        $exception = new UnknownException($original);

        $this->assertSame(99, $exception->context['original_code']);
    }

    public function test_context_captures_original_file_and_line(): void
    {
        $original  = new RuntimeException('Error');
        $exception = new UnknownException($original);

        $this->assertSame($original->getFile(), $exception->context['original_file']);
        $this->assertSame($original->getLine(), $exception->context['original_line']);
    }

    public function test_correlation_id_is_null_when_not_provided(): void
    {
        $this->assertNull((new UnknownException(new RuntimeException('Error')))->correlationId);
    }

    public function test_accepts_custom_correlation_id(): void
    {
        $exception = new UnknownException(new RuntimeException('Error'), 'custom-id');

        $this->assertSame('custom-id', $exception->correlationId);
    }

    public function test_wraps_non_runtime_exceptions(): void
    {
        $original  = new \LogicException('Logic failure', 7);
        $exception = new UnknownException($original);

        $this->assertSame('Logic failure', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
        $this->assertSame(\LogicException::class, $exception->context['original_class']);
    }
}
