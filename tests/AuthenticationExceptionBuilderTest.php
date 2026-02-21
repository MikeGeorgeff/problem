<?php

namespace Georgeff\Problem\Test;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\AuthenticationExceptionBuilder;
use Georgeff\Problem\Exception\AuthenticationException;

class AuthenticationExceptionBuilderTest extends TestCase
{
    public function test_new_creates_authentication_exception_builder(): void
    {
        $this->assertInstanceOf(AuthenticationExceptionBuilder::class, AuthenticationExceptionBuilder::new());
    }

    public function test_new_defaults_to_retryable(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenInvalid()
            ->build();

        $this->assertTrue($exception->retryable);
    }

    public function test_new_defaults_severity_to_warning(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenInvalid()
            ->build();

        $this->assertSame('warning', $exception->severity);
    }

    public function test_builds_authentication_exception_instance(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenInvalid()
            ->build();

        $this->assertInstanceOf(AuthenticationException::class, $exception);
    }

    public function test_throw_throws_authentication_exception(): void
    {
        $this->expectException(AuthenticationException::class);

        AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenInvalid()
            ->throw();
    }

    public function test_build_throws_logic_exception_without_user(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('User ID must be set');

        AuthenticationExceptionBuilder::new()->tokenInvalid()->build();
    }

    // --- user ---

    public function test_user_sets_user_id_in_context(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenInvalid()
            ->build();

        $this->assertSame('user-123', $exception->context['user_id']);
    }

    // --- context helpers ---

    public function test_token_type_adds_to_context(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenType('JWT')
            ->tokenInvalid()
            ->build();

        $this->assertSame('JWT', $exception->context['authn_token_type']);
    }

    public function test_token_expiry_adds_rfc3339_string_to_context(): void
    {
        $expiry    = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenExpiry($expiry)
            ->tokenExpired()
            ->build();

        $this->assertSame($expiry->format(\DateTimeInterface::RFC3339), $exception->context['authn_token_exp']);
    }

    // --- scenario methods ---

    public function test_token_invalid(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenInvalid()
            ->build();

        $this->assertSame('Authentication token for user [user-123] is invalid', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
    }

    public function test_token_expired(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->tokenExpired()
            ->build();

        $this->assertSame('Authentication token for user [user-123] is expired', $exception->getMessage());
        $this->assertSame(2, $exception->getCode());
    }

    public function test_credentials_invalid(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->credentialsInvalid()
            ->build();

        $this->assertSame('Authentication credentials for user [user-123] are invalid', $exception->getMessage());
        $this->assertSame(3, $exception->getCode());
    }

    public function test_session_not_found(): void
    {
        $exception = AuthenticationExceptionBuilder::new()
            ->user('user-123')
            ->sessionNotFound()
            ->build();

        $this->assertSame('Authentication session for user [user-123] was not found', $exception->getMessage());
        $this->assertSame(4, $exception->getCode());
    }

    public function test_user_id_appears_in_all_scenario_messages(): void
    {
        $builder = AuthenticationExceptionBuilder::new()->user('user-abc');

        $messages = [
            (clone $builder)->tokenInvalid()->build()->getMessage(),
            (clone $builder)->tokenExpired()->build()->getMessage(),
            (clone $builder)->credentialsInvalid()->build()->getMessage(),
            (clone $builder)->sessionNotFound()->build()->getMessage(),
        ];

        foreach ($messages as $message) {
            $this->assertStringContainsString('user-abc', $message);
        }
    }
}
