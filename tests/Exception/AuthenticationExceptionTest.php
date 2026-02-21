<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\AuthenticationException;
use Georgeff\Problem\Exception\DomainException;
use RuntimeException;

class AuthenticationExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new AuthenticationException('Error'));
    }

    public function test_default_severity_is_warning(): void
    {
        $this->assertSame('warning', (new AuthenticationException('Error'))->severity);
    }

    public function test_default_retryable_is_true(): void
    {
        $this->assertTrue((new AuthenticationException('Error'))->retryable);
    }

    public function test_error_code_uses_authn_prefix(): void
    {
        $this->assertStringStartsWith('AUTHN_', (new AuthenticationException('Error'))->getErrorCode());
    }

    public function test_error_code_pads_to_four_digits(): void
    {
        $this->assertSame('AUTHN_0000', (new AuthenticationException('Error', code: 0))->getErrorCode());
        $this->assertSame('AUTHN_0001', (new AuthenticationException('Error', code: 1))->getErrorCode());
        $this->assertSame('AUTHN_0099', (new AuthenticationException('Error', code: 99))->getErrorCode());
        $this->assertSame('AUTHN_9999', (new AuthenticationException('Error', code: 9999))->getErrorCode());
    }

    public function test_accepts_all_constructor_parameters(): void
    {
        $previous  = new RuntimeException('Previous');
        $exception = new AuthenticationException(
            message:       'Token invalid',
            severity:      'error',
            context:       ['user_id' => '123'],
            correlationId: 'corr-id',
            retryable:     false,
            code:          1,
            previous:      $previous
        );

        $this->assertSame('Token invalid', $exception->getMessage());
        $this->assertSame('error', $exception->severity);
        $this->assertSame(['user_id' => '123'], $exception->context);
        $this->assertSame('corr-id', $exception->correlationId);
        $this->assertFalse($exception->retryable);
        $this->assertSame(1, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
