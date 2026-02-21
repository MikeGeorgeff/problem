<?php

namespace Georgeff\Problem\Contract;

use Throwable;
use Georgeff\Problem\Exception\DomainException;

interface Translator
{
    /**
     * Register a translator handler
     *
     * @param callable(Throwable): bool $pattern
     * @param callable(Throwable, ?string): DomainException $callback
     */
    public function register(callable $pattern, callable $callback, int $priority = 0): static;

    /**
     * Convert an exception into a domain exception
     */
    public function translate(Throwable $exception, ?string $correlationId = null): DomainException;
}
