<?php

namespace Georgeff\Problem\Translation;

use Throwable;
use Georgeff\Problem\Contract\Translator;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\UnknownException;

final class ExceptionTranslator implements Translator
{
    /**
     * @var list<array{pattern: callable(Throwable): bool, callback: callable(Throwable, ?string): DomainException, priority: int}>
     */
    private array $handlers = [];

    /**
     * @inheritdoc
     */
    public function register(callable $pattern, callable $callback, int $priority = 0): static
    {
        $this->handlers[] = [
            'pattern'  => $pattern,
            'callback' => $callback,
            'priority' => $priority,
        ];

        usort(
            $this->handlers,
            fn($a, $b) => $b['priority'] <=> $a['priority']
        );

        return $this;
    }

    public function translate(Throwable $exception, ?string $correlationId = null): DomainException
    {
        if ($exception instanceof DomainException) {
            return $exception;
        }

        $handler = array_find(
            $this->handlers,
            fn($h, $_) => ($h['pattern'])($exception)
        );

        if (null === $handler) {
            return new UnknownException($exception, $correlationId);
        }

        return $handler['callback']($exception, $correlationId);
    }
}
