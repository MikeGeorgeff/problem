<?php

namespace Georgeff\Problem\Test\Reporting;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Contract\ExceptionReporter as ExceptionReporterContract;
use Georgeff\Problem\Exception\DatabaseException;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\UnknownException;
use Georgeff\Problem\Logging\JsonStructuredLogger;
use Georgeff\Problem\Reporting\ExceptionReporter;
use Georgeff\Problem\Translation\ExceptionTranslator;

class ExceptionReporterTest extends TestCase
{
    private function makeOutput(): mixed
    {
        return fopen('php://memory', 'rw');
    }

    private function decodeOutput(mixed $output): array
    {
        rewind($output);
        return json_decode(stream_get_contents($output), true);
    }

    private function makeLogger(mixed $output = null): JsonStructuredLogger
    {
        return new JsonStructuredLogger($output ?? $this->makeOutput());
    }

    private function makeReporter(mixed $output = null): ExceptionReporter
    {
        return new ExceptionReporter(new ExceptionTranslator(), $this->makeLogger($output));
    }

    // --- contract ---

    public function test_implements_exception_reporter_contract(): void
    {
        $this->assertInstanceOf(ExceptionReporterContract::class, $this->makeReporter());
    }

    // --- report ---

    public function test_report_returns_domain_exception(): void
    {
        $result = $this->makeReporter()->report(new RuntimeException('error'));

        $this->assertInstanceOf(DomainException::class, $result);
    }

    public function test_report_translates_exception_via_registered_handler(): void
    {
        $translator = new ExceptionTranslator();
        $translator->register(
            fn($e) => $e instanceof RuntimeException,
            fn($e) => new DatabaseException($e->getMessage())
        );

        $reporter = new ExceptionReporter($translator, $this->makeLogger());

        $result = $reporter->report(new RuntimeException('db error'));

        $this->assertInstanceOf(DatabaseException::class, $result);
        $this->assertSame('db error', $result->getMessage());
    }

    public function test_report_falls_back_to_unknown_exception_when_no_handler_matches(): void
    {
        $result = $this->makeReporter()->report(new RuntimeException('unhandled'));

        $this->assertInstanceOf(UnknownException::class, $result);
    }

    public function test_report_returns_domain_exception_passthrough(): void
    {
        $domain = new DatabaseException('db error');

        $result = $this->makeReporter()->report($domain);

        $this->assertSame($domain, $result);
    }

    // --- logging ---

    public function test_report_logs_the_translated_exception(): void
    {
        $output = $this->makeOutput();

        $this->makeReporter($output)->report(new RuntimeException('something failed'));

        $this->assertSame('something failed', $this->decodeOutput($output)['message']);
    }

    public function test_report_logs_at_exception_severity(): void
    {
        $output     = $this->makeOutput();
        $translator = new ExceptionTranslator();
        $translator->register(
            fn($e) => $e instanceof RuntimeException,
            fn($e) => new DatabaseException($e->getMessage(), severity: 'critical')
        );

        $reporter = new ExceptionReporter($translator, $this->makeLogger($output));
        $reporter->report(new RuntimeException('critical failure'));

        $this->assertSame('critical', $this->decodeOutput($output)['level']);
    }

    public function test_report_logs_domain_exception_passthrough(): void
    {
        $output = $this->makeOutput();
        $domain = new DatabaseException('db error');

        $this->makeReporter($output)->report($domain);

        $this->assertSame('db error', $this->decodeOutput($output)['message']);
    }

    // --- correlation id ---

    public function test_report_passes_correlation_id_to_translator(): void
    {
        $result = $this->makeReporter()->report(new RuntimeException('error'), 'test-correlation-id');

        $this->assertSame('test-correlation-id', $result->correlationId);
    }

    public function test_report_correlation_id_is_null_when_not_provided(): void
    {
        $result = $this->makeReporter()->report(new RuntimeException('error'));

        $this->assertNull($result->correlationId);
    }
}
