<?php

namespace Georgeff\Problem\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\ExternalServiceExceptionBuilder;
use Georgeff\Problem\Exception\ExternalServiceException;

class ExternalServiceExceptionBuilderTest extends TestCase
{
    public function test_new_creates_external_service_exception_builder(): void
    {
        $this->assertInstanceOf(ExternalServiceExceptionBuilder::class, ExternalServiceExceptionBuilder::new());
    }

    public function test_new_defaults_to_retryable(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->unavailable()
            ->build();

        $this->assertTrue($exception->retryable);
    }

    public function test_builds_external_service_exception_instance(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->unavailable()
            ->build();

        $this->assertInstanceOf(ExternalServiceException::class, $exception);
    }

    public function test_throw_throws_external_service_exception(): void
    {
        $this->expectException(ExternalServiceException::class);

        ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->unavailable()
            ->throw();
    }

    public function test_build_throws_logic_exception_without_service(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service name is required');

        ExternalServiceExceptionBuilder::new()->unavailable()->build();
    }

    public function test_service_is_added_to_context_on_build(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->unavailable()
            ->build();

        $this->assertSame('stripe', $exception->context['service']);
    }

    // --- scenario methods ---

    public function test_timeout(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->timeout(30)
            ->build();

        $this->assertSame('External service [stripe] timed out after 30s', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
        $this->assertSame(30, $exception->context['timeout_seconds']);
        $this->assertTrue($exception->retryable);
    }

    public function test_unavailable(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->unavailable()
            ->build();

        $this->assertSame('External service [stripe] is unavailable', $exception->getMessage());
        $this->assertSame(2, $exception->getCode());
        $this->assertTrue($exception->retryable);
    }

    public function test_authentication(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->authentication()
            ->build();

        $this->assertSame('External service [stripe] authentication failed', $exception->getMessage());
        $this->assertSame(3, $exception->getCode());
    }

    public function test_rate_limited(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->rateLimited(60)
            ->build();

        $this->assertSame('External service [stripe] rate limit exceeded', $exception->getMessage());
        $this->assertSame(4, $exception->getCode());
        $this->assertSame(60, $exception->context['retry_after_seconds']);
        $this->assertTrue($exception->retryable);
    }

    public function test_connection_failed_without_reason(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->connectionFailed()
            ->build();

        $this->assertSame('External service [stripe] connection failed', $exception->getMessage());
        $this->assertSame(5, $exception->getCode());
        $this->assertTrue($exception->retryable);
        $this->assertArrayNotHasKey('connection_failure_reason', $exception->context);
    }

    public function test_connection_failed_with_reason(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->connectionFailed('Connection refused')
            ->build();

        $this->assertSame('External service [stripe] connection failed', $exception->getMessage());
        $this->assertSame('Connection refused', $exception->context['connection_failure_reason']);
    }

    public function test_invalid_response(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->invalidResponse()
            ->build();

        $this->assertSame('External service [stripe] returned an invalid response', $exception->getMessage());
        $this->assertSame(6, $exception->getCode());
        $this->assertFalse($exception->retryable);
    }

    // --- context helpers ---

    public function test_endpoint_stores_uppercased_method_and_uri(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->unavailable()
            ->endpoint('post', '/v1/charges')
            ->build();

        $this->assertSame('POST', $exception->context['method']);
        $this->assertSame('/v1/charges', $exception->context['uri']);
    }

    public function test_status_code_adds_http_status_to_context(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->unavailable()
            ->statusCode(503)
            ->build();

        $this->assertSame(503, $exception->context['http_status']);
    }

    public function test_response_body_adds_body_to_context(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->invalidResponse()
            ->responseBody('unexpected html response')
            ->build();

        $this->assertSame('unexpected html response', $exception->context['response_body']);
    }

    public function test_response_time_adds_milliseconds_to_context(): void
    {
        $exception = ExternalServiceExceptionBuilder::new()
            ->service('stripe')
            ->timeout(5)
            ->responseTime(5432.1)
            ->build();

        $this->assertSame(5432.1, $exception->context['response_time_ms']);
    }

    public function test_service_name_appears_in_all_scenario_messages(): void
    {
        $builder = ExternalServiceExceptionBuilder::new()->service('my-api');

        $scenarios = [
            (clone $builder)->timeout(10)->build()->getMessage(),
            (clone $builder)->unavailable()->build()->getMessage(),
            (clone $builder)->authentication()->build()->getMessage(),
            (clone $builder)->rateLimited(30)->build()->getMessage(),
            (clone $builder)->connectionFailed()->build()->getMessage(),
            (clone $builder)->invalidResponse()->build()->getMessage(),
        ];

        foreach ($scenarios as $message) {
            $this->assertStringContainsString('my-api', $message);
        }
    }
}
