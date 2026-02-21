<?php

namespace Georgeff\Problem\Test;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\NotFoundExceptionBuilder;
use Georgeff\Problem\Exception\NotFoundException;

class NotFoundExceptionBuilderTest extends TestCase
{
    public function test_new_creates_not_found_exception_builder(): void
    {
        $this->assertInstanceOf(NotFoundExceptionBuilder::class, NotFoundExceptionBuilder::new());
    }

    public function test_new_defaults_to_not_retryable(): void
    {
        $exception = NotFoundExceptionBuilder::new()->resource('post')->build();

        $this->assertFalse($exception->retryable);
    }

    public function test_new_defaults_severity_to_info(): void
    {
        $exception = NotFoundExceptionBuilder::new()->resource('post')->build();

        $this->assertSame('info', $exception->severity);
    }

    public function test_builds_not_found_exception_instance(): void
    {
        $exception = NotFoundExceptionBuilder::new()->resource('post')->build();

        $this->assertInstanceOf(NotFoundException::class, $exception);
    }

    public function test_throw_throws_not_found_exception(): void
    {
        $this->expectException(NotFoundException::class);

        NotFoundExceptionBuilder::new()->resource('post')->throw();
    }

    // --- resource ---

    public function test_resource_without_identifier(): void
    {
        $exception = NotFoundExceptionBuilder::new()->resource('post')->build();

        $this->assertSame('Resource [post] not found', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
        $this->assertSame('post', $exception->context['resource']);
        $this->assertNull($exception->context['resource_id']);
    }

    public function test_resource_with_string_identifier(): void
    {
        $exception = NotFoundExceptionBuilder::new()->resource('post', 'my-slug')->build();

        $this->assertSame('Resource [post] not found', $exception->getMessage());
        $this->assertSame('my-slug', $exception->context['resource_id']);
    }

    public function test_resource_with_integer_identifier(): void
    {
        $exception = NotFoundExceptionBuilder::new()->resource('post', 42)->build();

        $this->assertSame(42, $exception->context['resource_id']);
    }

    // --- record ---

    public function test_record_with_string_id(): void
    {
        $exception = NotFoundExceptionBuilder::new()->record('user', 'abc-123')->build();

        $this->assertSame('Record [user] with identifier [abc-123] not found', $exception->getMessage());
        $this->assertSame(2, $exception->getCode());
        $this->assertSame('user', $exception->context['record']);
        $this->assertSame('abc-123', $exception->context['record_id']);
    }

    public function test_record_with_integer_id(): void
    {
        $exception = NotFoundExceptionBuilder::new()->record('order', 99)->build();

        $this->assertSame('Record [order] with identifier [99] not found', $exception->getMessage());
        $this->assertSame(99, $exception->context['record_id']);
    }

    // --- endpoint ---

    public function test_endpoint_uppercases_method(): void
    {
        $exception = NotFoundExceptionBuilder::new()->endpoint('/api/posts', 'get')->build();

        $this->assertSame('Endpoint not found: [GET] [/api/posts]', $exception->getMessage());
        $this->assertSame(3, $exception->getCode());
        $this->assertSame('GET', $exception->context['method']);
        $this->assertSame('/api/posts', $exception->context['endpoint']);
    }

    public function test_endpoint_stores_uppercased_method_in_context(): void
    {
        $exception = NotFoundExceptionBuilder::new()->endpoint('/api/posts', 'post')->build();

        $this->assertSame('POST', $exception->context['method']);
    }

    // --- file ---

    public function test_file(): void
    {
        $exception = NotFoundExceptionBuilder::new()->file('/var/app/config.json')->build();

        $this->assertSame('File not found: [/var/app/config.json]', $exception->getMessage());
        $this->assertSame(4, $exception->getCode());
        $this->assertSame('/var/app/config.json', $exception->context['file']);
    }
}
