<?php

namespace Georgeff\Problem\Test;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\ExceptionBuilder;
use Georgeff\Problem\Exception\DatabaseException;
use RuntimeException;
use stdClass;

class ExceptionBuilderTest extends TestCase
{
    public function test_rejects_class_that_does_not_extend_domain_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExceptionBuilder(RuntimeException::class);
    }

    public function test_rejects_non_exception_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExceptionBuilder(stdClass::class);
    }

    public function test_exception_factory_creates_instance(): void
    {
        $builder = ExceptionBuilder::exception(DatabaseException::class);

        $this->assertInstanceOf(ExceptionBuilder::class, $builder);
    }

    public function test_build_throws_logic_exception_without_message(): void
    {
        $this->expectException(LogicException::class);

        ExceptionBuilder::exception(DatabaseException::class)->build();
    }

    public function test_build_creates_correct_exception_type(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Something failed')
            ->build();

        $this->assertInstanceOf(DatabaseException::class, $exception);
    }

    public function test_build_sets_message(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Custom message')
            ->build();

        $this->assertSame('Custom message', $exception->getMessage());
    }

    public function test_default_severity_is_error(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->build();

        $this->assertSame('error', $exception->severity);
    }

    #[DataProvider('severityMethodProvider')]
    public function test_severity_methods_set_severity(string $method, string $expected): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->{$method}()
            ->build();

        $this->assertSame($expected, $exception->severity);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function severityMethodProvider(): array
    {
        return [
            'debug'    => ['debug', 'debug'],
            'info'     => ['info', 'info'],
            'warning'  => ['warning', 'warning'],
            'error'    => ['error', 'error'],
            'critical' => ['critical', 'critical'],
        ];
    }

    public function test_context_merges_successive_calls(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->context(['a' => 1])
            ->context(['b' => 2])
            ->build();

        $this->assertSame(['a' => 1, 'b' => 2], $exception->context);
    }

    public function test_context_later_keys_overwrite_earlier_keys(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->context(['key' => 'first'])
            ->context(['key' => 'second'])
            ->build();

        $this->assertSame('second', $exception->context['key']);
    }

    public function test_with_adds_single_context_key(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->with('key', 'value')
            ->build();

        $this->assertSame(['key' => 'value'], $exception->context);
    }

    public function test_correlation_id_is_passed_to_exception(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->correlationId('test-id-123')
            ->build();

        $this->assertSame('test-id-123', $exception->correlationId);
    }

    public function test_retryable_sets_retryable_true(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->retryable()
            ->build();

        $this->assertTrue($exception->retryable);
    }

    public function test_not_retryable_sets_retryable_false(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->retryable()
            ->notRetryable()
            ->build();

        $this->assertFalse($exception->retryable);
    }

    public function test_code_is_passed_to_exception(): void
    {
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->code(42)
            ->build();

        $this->assertSame(42, $exception->getCode());
    }

    public function test_previous_is_passed_to_exception(): void
    {
        $previous  = new RuntimeException('Original');
        $exception = ExceptionBuilder::exception(DatabaseException::class)
            ->message('Error')
            ->previous($previous)
            ->build();

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_throw_throws_the_built_exception(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Thrown error');

        ExceptionBuilder::exception(DatabaseException::class)
            ->message('Thrown error')
            ->throw();
    }

    public function test_fluent_methods_return_same_instance(): void
    {
        $builder = ExceptionBuilder::exception(DatabaseException::class);

        $this->assertSame($builder, $builder->message('msg'));
        $this->assertSame($builder, $builder->debug());
        $this->assertSame($builder, $builder->info());
        $this->assertSame($builder, $builder->warning());
        $this->assertSame($builder, $builder->error());
        $this->assertSame($builder, $builder->critical());
        $this->assertSame($builder, $builder->context([]));
        $this->assertSame($builder, $builder->with('k', 'v'));
        $this->assertSame($builder, $builder->correlationId('id'));
        $this->assertSame($builder, $builder->retryable());
        $this->assertSame($builder, $builder->notRetryable());
        $this->assertSame($builder, $builder->code(0));
        $this->assertSame($builder, $builder->previous(new RuntimeException()));
    }
}
