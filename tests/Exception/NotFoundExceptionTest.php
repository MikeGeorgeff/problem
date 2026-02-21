<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\NotFoundException;
use RuntimeException;

class NotFoundExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new NotFoundException('Error'));
    }

    public function test_default_severity_is_info(): void
    {
        $this->assertSame('info', (new NotFoundException('Error'))->severity);
    }

    public function test_default_retryable_is_false(): void
    {
        $this->assertFalse((new NotFoundException('Error'))->retryable);
    }

    public function test_error_code_uses_not_found_prefix(): void
    {
        $this->assertStringStartsWith('NOT_FOUND_', (new NotFoundException('Error'))->getErrorCode());
    }

    public function test_error_code_pads_to_four_digits(): void
    {
        $this->assertSame('NOT_FOUND_0000', (new NotFoundException('Error', code: 0))->getErrorCode());
        $this->assertSame('NOT_FOUND_0001', (new NotFoundException('Error', code: 1))->getErrorCode());
        $this->assertSame('NOT_FOUND_0099', (new NotFoundException('Error', code: 99))->getErrorCode());
        $this->assertSame('NOT_FOUND_9999', (new NotFoundException('Error', code: 9999))->getErrorCode());
    }

    public function test_accepts_all_constructor_parameters(): void
    {
        $previous  = new RuntimeException('Previous');
        $exception = new NotFoundException(
            message:       'Resource not found',
            severity:      'warning',
            context:       ['resource' => 'user'],
            correlationId: 'corr-id',
            retryable:     true,
            code:          1,
            previous:      $previous
        );

        $this->assertSame('Resource not found', $exception->getMessage());
        $this->assertSame('warning', $exception->severity);
        $this->assertSame(['resource' => 'user'], $exception->context);
        $this->assertSame('corr-id', $exception->correlationId);
        $this->assertTrue($exception->retryable);
        $this->assertSame(1, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
