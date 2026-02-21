<?php

namespace Georgeff\Problem\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\ConfigurationExceptionBuilder;
use Georgeff\Problem\Exception\ConfigurationException;

class ConfigurationExceptionBuilderTest extends TestCase
{
    public function test_new_creates_configuration_exception_builder(): void
    {
        $this->assertInstanceOf(ConfigurationExceptionBuilder::class, ConfigurationExceptionBuilder::new());
    }

    public function test_new_defaults_to_critical_severity(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('app.name')
            ->missing()
            ->build();

        $this->assertSame('critical', $exception->severity);
    }

    public function test_new_defaults_to_not_retryable(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('app.name')
            ->missing()
            ->build();

        $this->assertFalse($exception->retryable);
    }

    public function test_builds_configuration_exception_instance(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('app.name')
            ->missing()
            ->build();

        $this->assertInstanceOf(ConfigurationException::class, $exception);
    }

    public function test_throw_throws_configuration_exception(): void
    {
        $this->expectException(ConfigurationException::class);

        ConfigurationExceptionBuilder::new()->key('app.name')->missing()->throw();
    }

    public function test_build_throws_logic_exception_without_key(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Key must be set');

        ConfigurationExceptionBuilder::new()->missing()->build();
    }

    // --- key ---

    public function test_key_sets_config_key_in_context(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('database.host')
            ->missing()
            ->build();

        $this->assertSame('database.host', $exception->context['config_key']);
    }

    // --- missing ---

    public function test_missing(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('database.host')
            ->missing()
            ->build();

        $this->assertSame('Required config key [database.host] is missing', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
    }

    // --- invalid ---

    public function test_invalid(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('database.port')
            ->invalid('not-a-number', 'Must be an integer')
            ->build();

        $this->assertSame('Config key [database.port] is invalid', $exception->getMessage());
        $this->assertSame(2, $exception->getCode());
        $this->assertSame('not-a-number', $exception->context['invalid_value']);
        $this->assertSame('Must be an integer', $exception->context['reason']);
    }

    public function test_invalid_with_non_string_value(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('app.debug')
            ->invalid(['unexpected', 'array'], 'Must be a boolean')
            ->build();

        $this->assertSame(['unexpected', 'array'], $exception->context['invalid_value']);
    }

    // --- invalidType ---

    public function test_invalid_type(): void
    {
        $exception = ConfigurationExceptionBuilder::new()
            ->key('database.port')
            ->invalidType('3306', 'int', 'string')
            ->build();

        $this->assertSame('Config key [database.port] is invalid', $exception->getMessage());
        $this->assertSame(2, $exception->getCode());
        $this->assertSame('Invalid type', $exception->context['reason']);
        $this->assertSame('int', $exception->context['expected_type']);
        $this->assertSame('string', $exception->context['provided_type']);
        $this->assertSame('3306', $exception->context['invalid_value']);
    }

    public function test_key_appears_in_all_scenario_messages(): void
    {
        $builder = ConfigurationExceptionBuilder::new()->key('cache.ttl');

        $messages = [
            (clone $builder)->missing()->build()->getMessage(),
            (clone $builder)->invalid(-1, 'Must be positive')->build()->getMessage(),
            (clone $builder)->invalidType('-1', 'int', 'string')->build()->getMessage(),
        ];

        foreach ($messages as $message) {
            $this->assertStringContainsString('cache.ttl', $message);
        }
    }
}
