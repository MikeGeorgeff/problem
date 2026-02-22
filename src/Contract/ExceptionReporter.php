<?php

namespace Georgeff\Problem\Contract;

use Throwable;
use Georgeff\Problem\Exception\DomainException;

interface ExceptionReporter
{
    public function report(Throwable $exception, ?string $correlationId = null): DomainException;
}
