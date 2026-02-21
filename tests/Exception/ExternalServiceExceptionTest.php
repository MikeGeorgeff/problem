<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\ExternalServiceException;
use RuntimeException;

class ExternalServiceExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new ExternalServiceException('Error'));
    }

    public function test_default_severity_is_error(): void
    {
        $this->assertSame('error', (new ExternalServiceException('Error'))->severity);
    }

    public function test_default_retryable_is_true(): void
    {
        $this->assertTrue((new ExternalServiceException('Error'))->retryable);
    }

    public function test_error_code_uses_ext_prefix(): void
    {
        $this->assertStringStartsWith('EXT_', (new ExternalServiceException('Error'))->getErrorCode());
    }

    public function test_error_code_pads_to_four_digits(): void
    {
        $this->assertSame('EXT_0000', (new ExternalServiceException('Error', code: 0))->getErrorCode());
        $this->assertSame('EXT_0001', (new ExternalServiceException('Error', code: 1))->getErrorCode());
        $this->assertSame('EXT_0099', (new ExternalServiceException('Error', code: 99))->getErrorCode());
        $this->assertSame('EXT_9999', (new ExternalServiceException('Error', code: 9999))->getErrorCode());
    }

    public function test_accepts_all_constructor_parameters(): void
    {
        $previous  = new RuntimeException('Previous');
        $exception = new ExternalServiceException(
            message:       'Service failed',
            severity:      'critical',
            context:       ['service' => 'stripe'],
            correlationId: 'corr-id',
            retryable:     false,
            code:          3,
            previous:      $previous
        );

        $this->assertSame('Service failed', $exception->getMessage());
        $this->assertSame('critical', $exception->severity);
        $this->assertSame(['service' => 'stripe'], $exception->context);
        $this->assertSame('corr-id', $exception->correlationId);
        $this->assertFalse($exception->retryable);
        $this->assertSame(3, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
