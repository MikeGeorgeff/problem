<?php

namespace Georgeff\Problem\Test\Exception;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\ConfigurationException;
use Georgeff\Problem\Exception\DomainException;
use RuntimeException;

class ConfigurationExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new ConfigurationException('Error'));
    }

    public function test_default_severity_is_critical(): void
    {
        $this->assertSame('critical', (new ConfigurationException('Error'))->severity);
    }

    public function test_default_retryable_is_false(): void
    {
        $this->assertFalse((new ConfigurationException('Error'))->retryable);
    }

    public function test_error_code_uses_cfg_prefix(): void
    {
        $this->assertStringStartsWith('CFG_', (new ConfigurationException('Error'))->getErrorCode());
    }

    public function test_error_code_pads_to_four_digits(): void
    {
        $this->assertSame('CFG_0000', (new ConfigurationException('Error', code: 0))->getErrorCode());
        $this->assertSame('CFG_0001', (new ConfigurationException('Error', code: 1))->getErrorCode());
        $this->assertSame('CFG_0099', (new ConfigurationException('Error', code: 99))->getErrorCode());
        $this->assertSame('CFG_9999', (new ConfigurationException('Error', code: 9999))->getErrorCode());
    }

    public function test_accepts_all_constructor_parameters(): void
    {
        $previous  = new RuntimeException('Previous');
        $exception = new ConfigurationException(
            message:       'Config missing',
            severity:      'error',
            context:       ['config_key' => 'app.name'],
            correlationId: 'corr-id',
            retryable:     false,
            code:          1,
            previous:      $previous
        );

        $this->assertSame('Config missing', $exception->getMessage());
        $this->assertSame('error', $exception->severity);
        $this->assertSame(['config_key' => 'app.name'], $exception->context);
        $this->assertSame('corr-id', $exception->correlationId);
        $this->assertFalse($exception->retryable);
        $this->assertSame(1, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
