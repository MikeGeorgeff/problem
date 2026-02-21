<?php

namespace Georgeff\Problem\Test\Translation;

use PDOException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Exception\DatabaseException;
use Georgeff\Problem\Translation\ExceptionTranslator;
use Georgeff\Problem\Translation\PDOCatchAllHandler;

class PDOCatchAllHandlerTest extends TestCase
{
    private function makePdo(string $message = 'PDO error', string|int $code = 0): PDOException
    {
        return new PDOException($message, (int) $code);
    }

    // --- matches ---

    public function test_matches_pdo_exception(): void
    {
        $handler = new PDOCatchAllHandler();

        $this->assertTrue($handler->matches($this->makePdo()));
    }

    public function test_does_not_match_non_pdo_exception(): void
    {
        $handler = new PDOCatchAllHandler();

        $this->assertFalse($handler->matches(new RuntimeException('not pdo')));
    }

    // --- __invoke ---

    public function test_invoke_returns_database_exception(): void
    {
        $handler = new PDOCatchAllHandler();

        $this->assertInstanceOf(DatabaseException::class, $handler($this->makePdo()));
    }

    public function test_invoke_uses_pdo_message(): void
    {
        $handler = new PDOCatchAllHandler();

        $result = $handler($this->makePdo('connection refused'));

        $this->assertSame('connection refused', $result->getMessage());
    }

    public function test_invoke_sets_code_to_6(): void
    {
        $handler = new PDOCatchAllHandler();

        $this->assertSame(6, $handler($this->makePdo())->getCode());
    }

    public function test_invoke_sets_pdo_exception_as_previous(): void
    {
        $handler = new PDOCatchAllHandler();
        $pdo = $this->makePdo();

        $this->assertSame($pdo, $handler($pdo)->getPrevious());
    }

    public function test_invoke_captures_original_code_in_context(): void
    {
        $handler = new PDOCatchAllHandler();

        $result = $handler($this->makePdo('error', 1045));

        $this->assertSame(1045, $result->context['original_code']);
    }

    public function test_invoke_captures_original_file_in_context(): void
    {
        $handler = new PDOCatchAllHandler();
        $pdo = $this->makePdo();

        $result = $handler($pdo);

        $this->assertSame($pdo->getFile(), $result->context['original_file']);
    }

    public function test_invoke_captures_original_line_in_context(): void
    {
        $handler = new PDOCatchAllHandler();
        $pdo = $this->makePdo();

        $result = $handler($pdo);

        $this->assertSame($pdo->getLine(), $result->context['original_line']);
    }

    public function test_invoke_passes_correlation_id(): void
    {
        $handler = new PDOCatchAllHandler();

        $result = $handler($this->makePdo(), 'test-correlation-id');

        $this->assertSame('test-correlation-id', $result->correlationId);
    }

    public function test_invoke_correlation_id_is_null_when_not_provided(): void
    {
        $handler = new PDOCatchAllHandler();

        $this->assertNull($handler($this->makePdo())->correlationId);
    }

    // --- register ---

    public function test_register_adds_handler_to_translator(): void
    {
        $translator = new ExceptionTranslator();
        PDOCatchAllHandler::register($translator);

        $this->assertInstanceOf(DatabaseException::class, $translator->translate($this->makePdo()));
    }

    public function test_register_does_not_match_non_pdo_exceptions(): void
    {
        $translator = new ExceptionTranslator();
        PDOCatchAllHandler::register($translator);

        $result = $translator->translate(new RuntimeException('not pdo'));

        $this->assertNotInstanceOf(DatabaseException::class, $result);
    }

    public function test_register_uses_priority_zero(): void
    {
        $translator = new ExceptionTranslator();

        // register a higher-priority handler that also matches PDOException
        $translator->register(
            fn($e) => $e instanceof PDOException,
            fn($e) => new DatabaseException('higher priority'),
            priority: 10
        );

        PDOCatchAllHandler::register($translator);

        $result = $translator->translate($this->makePdo('original'));

        // higher priority handler should win
        $this->assertSame('higher priority', $result->getMessage());
    }
}
