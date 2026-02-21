<?php

namespace Georgeff\Problem\Test\Exception;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\DomainException;
use RuntimeException;

class DomainExceptionTest extends TestCase
{
    private function make(
        string $message = 'Test error',
        string $severity = 'error',
        array $context = [],
        ?string $correlationId = null,
        bool $retryable = false,
        int $code = 0,
        ?\Throwable $previous = null
    ): DomainException {
        return new class($message, $severity, $context, $correlationId, $retryable, $code, $previous) extends DomainException {
            public function getErrorCode(): string { return 'TEST_0001'; }
        };
    }

    public function test_extends_php_exception(): void
    {
        $this->assertInstanceOf(\Exception::class, $this->make());
    }

    public function test_message_is_stored(): void
    {
        $exception = $this->make(message: 'Something went wrong');

        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    public function test_auto_generates_correlation_id(): void
    {
        $exception = $this->make();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $exception->correlationId);
    }

    public function test_generated_correlation_ids_are_unique(): void
    {
        $a = $this->make();
        $b = $this->make();

        $this->assertNotSame($a->correlationId, $b->correlationId);
    }

    public function test_accepts_custom_correlation_id(): void
    {
        $exception = $this->make(correlationId: 'custom-correlation-id');

        $this->assertSame('custom-correlation-id', $exception->correlationId);
    }

    public function test_severity_is_stored(): void
    {
        $exception = $this->make(severity: 'warning');

        $this->assertSame('warning', $exception->severity);
    }

    public function test_severity_is_normalized_to_lowercase(): void
    {
        $exception = $this->make(severity: 'WARNING');

        $this->assertSame('warning', $exception->severity);
    }

    public function test_invalid_severity_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid severity level \[invalid\]/');

        $this->make(severity: 'invalid');
    }

    public function test_all_valid_severity_levels_are_accepted(): void
    {
        foreach (['critical', 'error', 'warning', 'info', 'debug'] as $level) {
            $exception = $this->make(severity: $level);

            $this->assertSame($level, $exception->severity);
        }
    }

    public function test_context_defaults_to_empty_array(): void
    {
        $this->assertSame([], $this->make()->context);
    }

    public function test_context_is_stored(): void
    {
        $exception = $this->make(context: ['user_id' => 42, 'action' => 'login']);

        $this->assertSame(['user_id' => 42, 'action' => 'login'], $exception->context);
    }

    public function test_retryable_defaults_to_false(): void
    {
        $this->assertFalse($this->make()->retryable);
    }

    public function test_retryable_is_stored(): void
    {
        $this->assertTrue($this->make(retryable: true)->retryable);
    }

    public function test_occurred_at_is_datetime_immutable(): void
    {
        $this->assertInstanceOf(DateTimeImmutable::class, $this->make()->occurredAt);
    }

    public function test_occurred_at_is_set_at_construction_time(): void
    {
        $before    = new DateTimeImmutable();
        $exception = $this->make();
        $after     = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $exception->occurredAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $exception->occurredAt->getTimestamp());
    }

    public function test_code_is_stored(): void
    {
        $this->assertSame(42, $this->make(code: 42)->getCode());
    }

    public function test_previous_exception_is_stored(): void
    {
        $previous = new RuntimeException('Previous');

        $this->assertSame($previous, $this->make(previous: $previous)->getPrevious());
    }

    public function test_metadata_contains_expected_keys(): void
    {
        $metadata = $this->make()->metadata;

        $this->assertArrayHasKey('file', $metadata);
        $this->assertArrayHasKey('line', $metadata);
        $this->assertArrayHasKey('class', $metadata);
        $this->assertArrayHasKey('php_version', $metadata);
    }

    public function test_metadata_php_version_matches_runtime(): void
    {
        $this->assertSame(PHP_VERSION, $this->make()->metadata['php_version']);
    }

    public function test_metadata_is_lazily_cached(): void
    {
        $exception = $this->make();

        $this->assertSame($exception->metadata, $exception->metadata);
    }

    public function test_structured_data_has_all_keys(): void
    {
        $data = $this->make()->structuredData;

        foreach (['error_code', 'message', 'severity', 'correlation_id', 'retryable', 'occurred_at', 'context', 'metadata'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    public function test_structured_data_values_match_properties(): void
    {
        $exception = $this->make(
            message:       'Test error',
            severity:      'critical',
            correlationId: 'abc-123',
            retryable:     true
        );

        $data = $exception->structuredData;

        $this->assertSame('TEST_0001', $data['error_code']);
        $this->assertSame('Test error', $data['message']);
        $this->assertSame('critical', $data['severity']);
        $this->assertSame('abc-123', $data['correlation_id']);
        $this->assertTrue($data['retryable']);
    }

    public function test_structured_data_occurred_at_is_rfc3339(): void
    {
        $occurredAt = $this->make()->structuredData['occurred_at'];

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $occurredAt);
    }

    public function test_error_code_appears_in_structured_data(): void
    {
        $this->assertSame('TEST_0001', $this->make()->structuredData['error_code']);
    }
}
