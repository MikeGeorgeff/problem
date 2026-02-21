<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\DatabaseException;
use Georgeff\Problem\Exception\DomainException;
use RuntimeException;

class DatabaseExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new DatabaseException('Error'));
    }

    public function test_default_severity_is_error(): void
    {
        $this->assertSame('error', (new DatabaseException('Error'))->severity);
    }

    public function test_error_code_uses_db_prefix(): void
    {
        $exception = new DatabaseException('Error', code: 1);

        $this->assertStringStartsWith('DB_', $exception->getErrorCode());
    }

    public function test_error_code_pads_to_four_digits(): void
    {
        $this->assertSame('DB_0000', (new DatabaseException('Error', code: 0))->getErrorCode());
        $this->assertSame('DB_0001', (new DatabaseException('Error', code: 1))->getErrorCode());
        $this->assertSame('DB_0099', (new DatabaseException('Error', code: 99))->getErrorCode());
        $this->assertSame('DB_9999', (new DatabaseException('Error', code: 9999))->getErrorCode());
    }

    public function test_accepts_all_constructor_parameters(): void
    {
        $previous = new RuntimeException('Previous');
        $exception = new DatabaseException(
            message:       'Test',
            severity:      'warning',
            context:       ['key' => 'value'],
            correlationId: 'corr-id',
            retryable:     true,
            code:          5,
            previous:      $previous
        );

        $this->assertSame('Test', $exception->getMessage());
        $this->assertSame('warning', $exception->severity);
        $this->assertSame(['key' => 'value'], $exception->context);
        $this->assertSame('corr-id', $exception->correlationId);
        $this->assertTrue($exception->retryable);
        $this->assertSame(5, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
