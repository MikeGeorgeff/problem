<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\AuthorizationException;
use Georgeff\Problem\Exception\DomainException;
use RuntimeException;

class AuthorizationExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new AuthorizationException('Error'));
    }

    public function test_default_severity_is_warning(): void
    {
        $this->assertSame('warning', (new AuthorizationException('Error'))->severity);
    }

    public function test_default_retryable_is_false(): void
    {
        $this->assertFalse((new AuthorizationException('Error'))->retryable);
    }

    public function test_error_code_uses_authz_prefix(): void
    {
        $this->assertStringStartsWith('AUTHZ_', (new AuthorizationException('Error'))->getErrorCode());
    }

    public function test_error_code_pads_to_four_digits(): void
    {
        $this->assertSame('AUTHZ_0000', (new AuthorizationException('Error', code: 0))->getErrorCode());
        $this->assertSame('AUTHZ_0001', (new AuthorizationException('Error', code: 1))->getErrorCode());
        $this->assertSame('AUTHZ_0099', (new AuthorizationException('Error', code: 99))->getErrorCode());
        $this->assertSame('AUTHZ_9999', (new AuthorizationException('Error', code: 9999))->getErrorCode());
    }

    public function test_accepts_all_constructor_parameters(): void
    {
        $previous  = new RuntimeException('Previous');
        $exception = new AuthorizationException(
            message:       'Forbidden',
            severity:      'error',
            context:       ['resource' => 'posts'],
            correlationId: 'corr-id',
            retryable:     true,
            code:          1,
            previous:      $previous
        );

        $this->assertSame('Forbidden', $exception->getMessage());
        $this->assertSame('error', $exception->severity);
        $this->assertSame(['resource' => 'posts'], $exception->context);
        $this->assertSame('corr-id', $exception->correlationId);
        $this->assertTrue($exception->retryable);
        $this->assertSame(1, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
