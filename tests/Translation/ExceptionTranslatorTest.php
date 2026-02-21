<?php

namespace Georgeff\Problem\Test\Translation;

use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Contract\Translator;
use Georgeff\Problem\Exception\DatabaseException;
use Georgeff\Problem\Exception\UnknownException;
use Georgeff\Problem\Exception\ValidationException;
use Georgeff\Problem\Translation\ExceptionTranslator;

class ExceptionTranslatorTest extends TestCase
{
    public function test_implements_translator_contract(): void
    {
        $this->assertInstanceOf(Translator::class, new ExceptionTranslator());
    }

    // --- register ---

    public function test_register_returns_static_for_chaining(): void
    {
        $translator = new ExceptionTranslator();

        $result = $translator->register(
            fn($e) => true,
            fn($e) => new UnknownException($e)
        );

        $this->assertSame($translator, $result);
    }

    public function test_register_can_be_chained(): void
    {
        $translator = new ExceptionTranslator();

        $translator
            ->register(fn($e) => $e instanceof RuntimeException, fn($e) => new DatabaseException($e->getMessage()))
            ->register(fn($e) => $e instanceof InvalidArgumentException, fn($e) => new ValidationException($e->getMessage()));

        $this->assertInstanceOf(DatabaseException::class, $translator->translate(new RuntimeException('db')));
        $this->assertInstanceOf(ValidationException::class, $translator->translate(new InvalidArgumentException('val')));
    }

    // --- translate: passthrough ---

    public function test_translate_returns_domain_exception_unchanged(): void
    {
        $translator = new ExceptionTranslator();
        $domain = new DatabaseException('Error');

        $this->assertSame($domain, $translator->translate($domain));
    }

    public function test_translate_domain_exception_passthrough_bypasses_registered_handlers(): void
    {
        $translator = new ExceptionTranslator();
        $translator->register(fn($e) => true, fn($e) => new ValidationException('replaced'));

        $domain = new DatabaseException('original');

        $this->assertSame($domain, $translator->translate($domain));
    }

    // --- translate: fallback ---

    public function test_translate_falls_back_to_unknown_exception_with_no_handlers(): void
    {
        $translator = new ExceptionTranslator();

        $result = $translator->translate(new RuntimeException('unhandled'));

        $this->assertInstanceOf(UnknownException::class, $result);
    }

    public function test_translate_falls_back_to_unknown_exception_when_no_handler_matches(): void
    {
        $translator = new ExceptionTranslator();
        $translator->register(fn($e) => $e instanceof InvalidArgumentException, fn($e) => new ValidationException($e->getMessage()));

        $result = $translator->translate(new RuntimeException('unmatched'));

        $this->assertInstanceOf(UnknownException::class, $result);
    }

    public function test_translate_unknown_exception_wraps_original(): void
    {
        $translator = new ExceptionTranslator();
        $original = new RuntimeException('original');

        $result = $translator->translate($original);

        $this->assertSame($original, $result->getPrevious());
    }

    // --- translate: handler matching ---

    public function test_translate_uses_matching_handler(): void
    {
        $translator = new ExceptionTranslator();
        $translator->register(
            fn($e) => $e instanceof RuntimeException,
            fn($e) => new DatabaseException($e->getMessage())
        );

        $result = $translator->translate(new RuntimeException('db error'));

        $this->assertInstanceOf(DatabaseException::class, $result);
        $this->assertSame('db error', $result->getMessage());
    }

    public function test_translate_skips_non_matching_handlers(): void
    {
        $translator = new ExceptionTranslator();
        $translator->register(
            fn($e) => $e instanceof InvalidArgumentException,
            fn($e) => new ValidationException($e->getMessage())
        );

        $result = $translator->translate(new RuntimeException('oops'));

        $this->assertInstanceOf(UnknownException::class, $result);
    }

    public function test_translate_matches_on_instanceof_subclass(): void
    {
        $translator = new ExceptionTranslator();
        $translator->register(
            fn($e) => $e instanceof RuntimeException,
            fn($e) => new DatabaseException($e->getMessage())
        );

        // InvalidArgumentException extends LogicException, not RuntimeException — should not match
        $this->assertInstanceOf(UnknownException::class, $translator->translate(new InvalidArgumentException('no match')));

        // RuntimeException subclass — should match
        $this->assertInstanceOf(DatabaseException::class, $translator->translate(new \OverflowException('match')));
    }

    // --- translate: priority ---

    public function test_translate_higher_priority_handler_runs_first(): void
    {
        $translator = new ExceptionTranslator();

        $translator->register(
            fn($e) => $e instanceof RuntimeException,
            fn($e) => new DatabaseException('low priority'),
            priority: 1
        );

        $translator->register(
            fn($e) => $e instanceof RuntimeException,
            fn($e) => new ValidationException('high priority'),
            priority: 10
        );

        $result = $translator->translate(new RuntimeException());

        $this->assertInstanceOf(ValidationException::class, $result);
        $this->assertSame('high priority', $result->getMessage());
    }

    public function test_translate_priority_order_is_independent_of_registration_order(): void
    {
        $translatorA = new ExceptionTranslator();
        $translatorA
            ->register(fn($e) => $e instanceof RuntimeException, fn($e) => new DatabaseException('first registered'), priority: 10)
            ->register(fn($e) => $e instanceof RuntimeException, fn($e) => new ValidationException('second registered'), priority: 20);

        $translatorB = new ExceptionTranslator();
        $translatorB
            ->register(fn($e) => $e instanceof RuntimeException, fn($e) => new ValidationException('first registered'), priority: 20)
            ->register(fn($e) => $e instanceof RuntimeException, fn($e) => new DatabaseException('second registered'), priority: 10);

        $this->assertInstanceOf(ValidationException::class, $translatorA->translate(new RuntimeException()));
        $this->assertInstanceOf(ValidationException::class, $translatorB->translate(new RuntimeException()));
    }

    // --- translate: correlation id ---

    public function test_translate_passes_correlation_id_to_unknown_exception_fallback(): void
    {
        $translator = new ExceptionTranslator();

        $result = $translator->translate(new RuntimeException('oops'), 'test-correlation-id');

        $this->assertSame('test-correlation-id', $result->correlationId);
    }

    public function test_translate_unknown_exception_has_null_correlation_id_when_none_provided(): void
    {
        $translator = new ExceptionTranslator();

        $result = $translator->translate(new RuntimeException('oops'));

        $this->assertNull($result->correlationId);
    }

    public function test_translate_passes_correlation_id_to_handler_callback(): void
    {
        $translator = new ExceptionTranslator();
        $translator->register(
            fn($e) => $e instanceof RuntimeException,
            fn($e, $id) => new DatabaseException($e->getMessage(), correlationId: $id)
        );

        $result = $translator->translate(new RuntimeException('db'), 'test-correlation-id');

        $this->assertSame('test-correlation-id', $result->correlationId);
    }

    public function test_translate_domain_exception_passthrough_preserves_its_own_correlation_id(): void
    {
        $translator = new ExceptionTranslator();
        $domain = new DatabaseException('Error', correlationId: 'original-id');

        $result = $translator->translate($domain, 'different-id');

        $this->assertSame('original-id', $result->correlationId);
    }
}
