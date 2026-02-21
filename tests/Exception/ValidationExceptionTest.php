<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\ValidationException;

class ValidationExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new ValidationException('Error'));
    }

    public function test_default_severity_is_warning(): void
    {
        $this->assertSame('warning', (new ValidationException('Error'))->severity);
    }

    public function test_severity_can_be_overridden(): void
    {
        $this->assertSame('error', (new ValidationException('Error', severity: 'error'))->severity);
    }

    public function test_is_not_retryable_by_default(): void
    {
        $this->assertFalse((new ValidationException('Error'))->retryable);
    }

    public function test_error_code_uses_val_prefix(): void
    {
        $this->assertStringStartsWith('VAL_', (new ValidationException('Error'))->getErrorCode());
    }

    public function test_error_code_pads_to_four_digits(): void
    {
        $this->assertSame('VAL_0000', (new ValidationException('Error', code: 0))->getErrorCode());
        $this->assertSame('VAL_0001', (new ValidationException('Error', code: 1))->getErrorCode());
        $this->assertSame('VAL_0099', (new ValidationException('Error', code: 99))->getErrorCode());
        $this->assertSame('VAL_9999', (new ValidationException('Error', code: 9999))->getErrorCode());
    }

    public function test_accepts_all_constructor_parameters(): void
    {
        $previous  = new \RuntimeException('Previous');
        $exception = new ValidationException(
            message:       'Invalid input',
            severity:      'error',
            context:       ['field' => 'email'],
            correlationId: 'corr-id',
            retryable:     false,
            code:          5,
            previous:      $previous
        );

        $this->assertSame('Invalid input', $exception->getMessage());
        $this->assertSame('error', $exception->severity);
        $this->assertSame(['field' => 'email'], $exception->context);
        $this->assertSame('corr-id', $exception->correlationId);
        $this->assertFalse($exception->retryable);
        $this->assertSame(5, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
