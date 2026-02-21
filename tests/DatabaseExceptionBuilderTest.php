<?php

namespace Georgeff\Problem\Test;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\DatabaseExceptionBuilder;
use Georgeff\Problem\Exception\DatabaseException;

class DatabaseExceptionBuilderTest extends TestCase
{
    public function test_new_creates_database_exception_builder(): void
    {
        $this->assertInstanceOf(DatabaseExceptionBuilder::class, DatabaseExceptionBuilder::new());
    }

    public function test_builds_database_exception_instance(): void
    {
        $exception = DatabaseExceptionBuilder::new()
            ->connectionFailed()
            ->build();

        $this->assertInstanceOf(DatabaseException::class, $exception);
    }

    public function test_throw_throws_database_exception(): void
    {
        $this->expectException(DatabaseException::class);

        DatabaseExceptionBuilder::new()->connectionFailed()->throw();
    }

    // --- connectionFailed ---

    public function test_connection_failed_without_reason(): void
    {
        $exception = DatabaseExceptionBuilder::new()->connectionFailed()->build();

        $this->assertSame('Database connection failed', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
        $this->assertTrue($exception->retryable);
    }

    public function test_connection_failed_with_reason(): void
    {
        $exception = DatabaseExceptionBuilder::new()->connectionFailed('Host unreachable')->build();

        $this->assertSame('Database connection failed: Host unreachable', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
        $this->assertTrue($exception->retryable);
    }

    // --- deadlock ---

    public function test_deadlock(): void
    {
        $exception = DatabaseExceptionBuilder::new()->deadlock()->build();

        $this->assertSame('Database deadlock detected', $exception->getMessage());
        $this->assertSame(2, $exception->getCode());
        $this->assertTrue($exception->retryable);
    }

    // --- constraintViolation ---

    public function test_constraint_violation_message_and_code(): void
    {
        $exception = DatabaseExceptionBuilder::new()->constraintViolation('unique_email')->build();

        $this->assertSame('Database constraint violation: unique_email', $exception->getMessage());
        $this->assertSame(3, $exception->getCode());
    }

    public function test_constraint_violation_is_not_retryable(): void
    {
        $exception = DatabaseExceptionBuilder::new()->constraintViolation('unique_email')->build();

        $this->assertFalse($exception->retryable);
    }

    public function test_constraint_violation_adds_constraint_to_context(): void
    {
        $exception = DatabaseExceptionBuilder::new()->constraintViolation('unique_email')->build();

        $this->assertSame('unique_email', $exception->context['constraint']);
    }

    // --- timeout ---

    public function test_timeout(): void
    {
        $exception = DatabaseExceptionBuilder::new()->timeout(30)->build();

        $this->assertSame('Database query timed out after 30s', $exception->getMessage());
        $this->assertSame(4, $exception->getCode());
        $this->assertTrue($exception->retryable);
        $this->assertSame(30, $exception->context['timeout_seconds']);
    }

    // --- transaction ---

    public function test_transaction_defaults_to_commit(): void
    {
        $exception = DatabaseExceptionBuilder::new()->transaction()->build();

        $this->assertSame('Database transaction commit failed', $exception->getMessage());
        $this->assertSame(5, $exception->getCode());
        $this->assertTrue($exception->retryable);
        $this->assertSame('commit', $exception->context['transaction_operation']);
    }

    public function test_transaction_with_custom_operation(): void
    {
        $exception = DatabaseExceptionBuilder::new()->transaction('rollback')->build();

        $this->assertSame('Database transaction rollback failed', $exception->getMessage());
        $this->assertSame('rollback', $exception->context['transaction_operation']);
    }

    // --- context helpers ---

    public function test_query_sets_sql_and_params_in_context(): void
    {
        $exception = DatabaseExceptionBuilder::new()
            ->connectionFailed()
            ->query('SELECT * FROM users WHERE id = ?', [1])
            ->build();

        $this->assertSame([
            'sql'    => 'SELECT * FROM users WHERE id = ?',
            'params' => [1],
        ], $exception->context['query']);
    }

    public function test_query_defaults_params_to_empty_array(): void
    {
        $exception = DatabaseExceptionBuilder::new()
            ->connectionFailed()
            ->query('SELECT 1')
            ->build();

        $this->assertSame([], $exception->context['query']['params']);
    }

    public function test_connection_sets_host_port_and_database_in_context(): void
    {
        $exception = DatabaseExceptionBuilder::new()
            ->connectionFailed()
            ->connection('localhost', 5432, 'mydb')
            ->build();

        $this->assertSame([
            'host'     => 'localhost',
            'port'     => 5432,
            'database' => 'mydb',
        ], $exception->context['connection']);
    }

    public function test_table_sets_table_in_context(): void
    {
        $exception = DatabaseExceptionBuilder::new()
            ->deadlock()
            ->table('users')
            ->build();

        $this->assertSame('users', $exception->context['table']);
    }

    public function test_constraint_sets_constraint_in_context(): void
    {
        $exception = DatabaseExceptionBuilder::new()
            ->deadlock()
            ->constraint('fk_order_user')
            ->build();

        $this->assertSame('fk_order_user', $exception->context['constraint']);
    }
}
