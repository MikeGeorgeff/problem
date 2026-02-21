<?php

namespace Georgeff\Problem\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\AuthorizationExceptionBuilder;
use Georgeff\Problem\Exception\AuthorizationException;

class AuthorizationExceptionBuilderTest extends TestCase
{
    public function test_new_creates_authorization_exception_builder(): void
    {
        $this->assertInstanceOf(AuthorizationExceptionBuilder::class, AuthorizationExceptionBuilder::new());
    }

    public function test_new_defaults_to_not_retryable(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->forbidden()
            ->build();

        $this->assertFalse($exception->retryable);
    }

    public function test_new_defaults_severity_to_warning(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->forbidden()
            ->build();

        $this->assertSame('warning', $exception->severity);
    }

    public function test_builds_authorization_exception_instance(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->forbidden()
            ->build();

        $this->assertInstanceOf(AuthorizationException::class, $exception);
    }

    public function test_throw_throws_authorization_exception(): void
    {
        $this->expectException(AuthorizationException::class);

        AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->forbidden()
            ->throw();
    }

    public function test_build_throws_logic_exception_without_on(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Resource and action must be set');

        AuthorizationExceptionBuilder::new()->forbidden()->build();
    }

    public function test_build_throws_logic_exception_with_only_resource_set(): void
    {
        $this->expectException(LogicException::class);

        AuthorizationExceptionBuilder::new()
            ->on('posts', '')
            ->forbidden()
            ->build();
    }

    public function test_build_throws_logic_exception_with_only_action_set(): void
    {
        $this->expectException(LogicException::class);

        AuthorizationExceptionBuilder::new()
            ->on('', 'delete')
            ->forbidden()
            ->build();
    }

    // --- on ---

    public function test_on_sets_resource_and_action_in_context(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->forbidden()
            ->build();

        $this->assertSame('posts', $exception->context['authz_resource']);
        $this->assertSame('delete', $exception->context['authz_action']);
    }

    // --- context helpers ---

    public function test_scopes_adds_to_context(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->insufficientScope()
            ->scopes(['posts:read', 'posts:write'])
            ->build();

        $this->assertSame(['posts:read', 'posts:write'], $exception->context['authz_scopes']);
    }

    public function test_permissions_adds_to_context(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->tokenPermissions()
            ->permissions(['posts.read'])
            ->build();

        $this->assertSame(['posts.read'], $exception->context['authz_permissions']);
    }

    // --- scenario methods ---

    public function test_forbidden(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->forbidden()
            ->build();

        $this->assertSame('Forbidden: [delete] on [posts]', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
    }

    public function test_insufficient_scope(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->insufficientScope()
            ->build();

        $this->assertSame('Insufficient Scope: [delete] on [posts]', $exception->getMessage());
        $this->assertSame(2, $exception->getCode());
    }

    public function test_token_permissions(): void
    {
        $exception = AuthorizationExceptionBuilder::new()
            ->on('posts', 'delete')
            ->tokenPermissions()
            ->build();

        $this->assertSame('Token Lacks Permissions: [delete] on [posts]', $exception->getMessage());
        $this->assertSame(3, $exception->getCode());
    }

    public function test_resource_and_action_appear_in_all_scenario_messages(): void
    {
        $builder = AuthorizationExceptionBuilder::new()->on('invoices', 'write');

        $messages = [
            (clone $builder)->forbidden()->build()->getMessage(),
            (clone $builder)->insufficientScope()->build()->getMessage(),
            (clone $builder)->tokenPermissions()->build()->getMessage(),
        ];

        foreach ($messages as $message) {
            $this->assertStringContainsString('invoices', $message);
            $this->assertStringContainsString('write', $message);
        }
    }
}
