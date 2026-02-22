<?php

namespace Georgeff\Problem\Reporting;

use Throwable;
use Georgeff\Problem\Contract\Translator;
use Georgeff\Problem\Contract\StructuredLogger;
use Georgeff\Problem\Exception\DomainException;

final class ExceptionReporter implements \Georgeff\Problem\Contract\ExceptionReporter
{
    public function __construct(
        private readonly Translator $translator,
        private readonly StructuredLogger $logger
    ) {}

    public function report(Throwable $exception, ?string $correlationId = null): DomainException
    {
        $e = $this->translator->translate($exception, $correlationId);

        $this->logger->logException($e);

        return $e;
    }
}
