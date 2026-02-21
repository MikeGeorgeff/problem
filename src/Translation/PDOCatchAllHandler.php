<?php

namespace Georgeff\Problem\Translation;

use Throwable;
use PDOException;
use Georgeff\Problem\Contract\Translator;
use Georgeff\Problem\DatabaseExceptionBuilder;
use Georgeff\Problem\Exception\DomainException;

final class PDOCatchAllHandler
{
    public static function register(Translator $translator): void
    {
        $self = new self();

        $translator->register(fn(Throwable $e) => $self->matches($e), $self, 0);
    }

    public function __invoke(Throwable $e, ?string $correlationId = null): DomainException
    {
        /** @var PDOException $e */
        return DatabaseExceptionBuilder::new()
            ->code(6)
            ->previous($e)
            ->message($e->getMessage())
            ->correlationId($correlationId)
            ->context([
                'original_code' => $e->getCode(),
                'original_file' => $e->getFile(),
                'original_line' => $e->getLine(),
            ])
            ->build();
    }

    public function matches(Throwable $e): bool
    {
        return $e instanceof PDOException;
    }
}
