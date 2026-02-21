<?php

namespace Georgeff\Problem\Contract;

use Psr\Log\LoggerInterface;
use Georgeff\Problem\Exception\DomainException;

interface StructuredLogger extends LoggerInterface
{
    public function logException(DomainException $exception): void;
}
