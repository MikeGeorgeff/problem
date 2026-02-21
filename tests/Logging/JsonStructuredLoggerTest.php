<?php

namespace Georgeff\Problem\Test\Logging;

use Stringable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Georgeff\Problem\Contract\ContextEnricher;
use Georgeff\Problem\Contract\StructuredLogger;
use Georgeff\Problem\Exception\DatabaseException;
use Georgeff\Problem\Logging\JsonStructuredLogger;

class JsonStructuredLoggerTest extends TestCase
{
    private function makeOutput(): mixed
    {
        return fopen('php://memory', 'rw');
    }

    private function readOutput(mixed $output): string
    {
        rewind($output);
        return stream_get_contents($output);
    }

    private function decodeOutput(mixed $output): array
    {
        return json_decode($this->readOutput($output), true);
    }

    private function makeLogger(mixed $output = null, string $logLevel = 'debug', ContextEnricher ...$enrichers): JsonStructuredLogger
    {
        return new JsonStructuredLogger($output ?? $this->makeOutput(), $logLevel, ...$enrichers);
    }

    // --- constructor ---

    public function test_implements_structured_logger_contract(): void
    {
        $this->assertInstanceOf(StructuredLogger::class, $this->makeLogger());
    }

    public function test_constructor_throws_for_invalid_output(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new JsonStructuredLogger('not-a-resource');
    }

    public function test_constructor_throws_for_invalid_log_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum log level [invalid] is not valid');

        $this->makeLogger(logLevel: 'invalid');
    }

    public function test_constructor_normalises_log_level_to_lowercase(): void
    {
        $output = $this->makeOutput();
        $logger = new JsonStructuredLogger($output, 'WARNING');

        $logger->warning('warn');
        $logger->info('filtered');

        $raw = $this->readOutput($output);
        $this->assertStringContainsString('warn', $raw);
        $this->assertStringNotContainsString('filtered', $raw);
    }

    // --- log level validation ---

    public function test_log_throws_for_non_string_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Log level must be a string');

        $this->makeLogger()->log(42, 'message');
    }

    public function test_log_throws_for_invalid_level_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Log level [invalid] is not valid');

        $this->makeLogger()->log('invalid', 'message');
    }

    public function test_log_accepts_stringable_level(): void
    {
        $output = $this->makeOutput();
        $level  = new class implements Stringable {
            public function __toString(): string { return 'error'; }
        };

        $this->makeLogger($output)->log($level, 'message');

        $this->assertStringContainsString('error', $this->readOutput($output));
    }

    public function test_log_normalises_level_to_lowercase(): void
    {
        $output = $this->makeOutput();

        $this->makeLogger($output)->log('ERROR', 'message');

        $this->assertSame('error', $this->decodeOutput($output)['level']);
    }

    // --- log entry structure ---

    public function test_log_entry_contains_required_fields(): void
    {
        $output = $this->makeOutput();

        $this->makeLogger($output)->error('something failed');

        $entry = $this->decodeOutput($output);

        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertArrayHasKey('level', $entry);
        $this->assertArrayHasKey('severity', $entry);
        $this->assertArrayHasKey('message', $entry);
        $this->assertArrayHasKey('context', $entry);
    }

    public function test_log_entry_level_is_psr3_level(): void
    {
        $output = $this->makeOutput();

        $this->makeLogger($output)->error('message');

        $this->assertSame('error', $this->decodeOutput($output)['level']);
    }

    public function test_log_entry_message_is_cast_to_string(): void
    {
        $output  = $this->makeOutput();
        $message = new class implements Stringable {
            public function __toString(): string { return 'stringable message'; }
        };

        $this->makeLogger($output)->error($message);

        $this->assertSame('stringable message', $this->decodeOutput($output)['message']);
    }

    public function test_log_entry_context_is_included(): void
    {
        $output = $this->makeOutput();

        $this->makeLogger($output)->error('message', ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $this->decodeOutput($output)['context']);
    }

    public function test_log_entry_is_newline_terminated(): void
    {
        $output = $this->makeOutput();

        $this->makeLogger($output)->error('message');

        $this->assertStringEndsWith(PHP_EOL, $this->readOutput($output));
    }

    // --- severity map ---

    #[DataProvider('severityMapProvider')]
    public function test_severity_map(string $level, string $expectedSeverity): void
    {
        $output = $this->makeOutput();

        $this->makeLogger($output)->log($level, 'message');

        $this->assertSame($expectedSeverity, $this->decodeOutput($output)['severity']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function severityMapProvider(): array
    {
        return [
            'emergency' => ['emergency', 'critical'],
            'alert'     => ['alert', 'critical'],
            'critical'  => ['critical', 'critical'],
            'error'     => ['error', 'error'],
            'warning'   => ['warning', 'warning'],
            'notice'    => ['notice', 'info'],
            'info'      => ['info', 'info'],
            'debug'     => ['debug', 'debug'],
        ];
    }

    // --- minimum log level filtering ---

    public function test_messages_below_minimum_level_are_not_logged(): void
    {
        $output = $this->makeOutput();
        $logger = $this->makeLogger($output, 'warning');

        $logger->debug('filtered');
        $logger->info('filtered');
        $logger->notice('filtered');

        $this->assertSame('', $this->readOutput($output));
    }

    public function test_messages_at_minimum_level_are_logged(): void
    {
        $output = $this->makeOutput();
        $logger = $this->makeLogger($output, 'warning');

        $logger->warning('logged');

        $this->assertStringContainsString('logged', $this->readOutput($output));
    }

    public function test_messages_above_minimum_level_are_logged(): void
    {
        $output = $this->makeOutput();
        $logger = $this->makeLogger($output, 'warning');

        $logger->error('logged');
        $logger->critical('logged');
        $logger->alert('logged');
        $logger->emergency('logged');

        $lines = array_filter(explode(PHP_EOL, trim($this->readOutput($output))));
        $this->assertCount(4, $lines);
    }

    // --- psr-3 level methods ---

    #[DataProvider('psr3LevelMethodProvider')]
    public function test_psr3_level_method(string $method): void
    {
        $output = $this->makeOutput();

        $this->makeLogger($output)->$method('message');

        $this->assertSame($method, $this->decodeOutput($output)['level']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function psr3LevelMethodProvider(): array
    {
        return [
            'debug'     => ['debug'],
            'info'      => ['info'],
            'notice'    => ['notice'],
            'warning'   => ['warning'],
            'error'     => ['error'],
            'critical'  => ['critical'],
            'alert'     => ['alert'],
            'emergency' => ['emergency'],
        ];
    }

    // --- enrichers ---

    public function test_enricher_is_applied_to_context(): void
    {
        $output   = $this->makeOutput();
        $enricher = new class implements ContextEnricher {
            public function enrich(array $context): array {
                return $context + ['enriched' => true];
            }
        };

        $this->makeLogger($output, 'debug', $enricher)->error('message');

        $this->assertTrue($this->decodeOutput($output)['context']['enriched']);
    }

    public function test_multiple_enrichers_are_applied_in_order(): void
    {
        $output = $this->makeOutput();

        $first = new class implements ContextEnricher {
            public function enrich(array $context): array {
                return $context + ['order' => 'first'];
            }
        };

        $second = new class implements ContextEnricher {
            public function enrich(array $context): array {
                return $context + ['order' => 'second'];
            }
        };

        $this->makeLogger($output, 'debug', $first, $second)->error('message');

        // first enricher wins since second uses + (non-overwriting merge)
        $this->assertSame('first', $this->decodeOutput($output)['context']['order']);
    }

    public function test_enrichers_do_not_run_for_filtered_log_entries(): void
    {
        $called   = false;
        $enricher = new class ($called) implements ContextEnricher {
            public function __construct(private bool &$called) {}
            public function enrich(array $context): array {
                $this->called = true;
                return $context;
            }
        };

        $this->makeLogger(logLevel: 'error', enrichers: $enricher)->debug('filtered');

        $this->assertFalse($called);
    }

    // --- logException ---

    public function test_log_exception_logs_at_exception_severity(): void
    {
        $output    = $this->makeOutput();
        $exception = new DatabaseException('db error', severity: 'error');

        $this->makeLogger($output)->logException($exception);

        $this->assertSame('error', $this->decodeOutput($output)['level']);
    }

    public function test_log_exception_uses_exception_message(): void
    {
        $output    = $this->makeOutput();
        $exception = new DatabaseException('db error');

        $this->makeLogger($output)->logException($exception);

        $this->assertSame('db error', $this->decodeOutput($output)['message']);
    }

    public function test_log_exception_context_contains_structured_data(): void
    {
        $output    = $this->makeOutput();
        $exception = new DatabaseException('db error');

        $this->makeLogger($output)->logException($exception);

        $context = $this->decodeOutput($output)['context'];

        $this->assertArrayHasKey('error_code', $context);
        $this->assertArrayHasKey('severity', $context);
        $this->assertArrayHasKey('retryable', $context);
        $this->assertArrayHasKey('context', $context);
        $this->assertArrayHasKey('metadata', $context);
    }

    public function test_log_exception_is_filtered_by_minimum_level(): void
    {
        $output    = $this->makeOutput();
        $exception = new DatabaseException('debug message', severity: 'debug');

        $this->makeLogger($output, 'error')->logException($exception);

        $this->assertSame('', $this->readOutput($output));
    }
}
