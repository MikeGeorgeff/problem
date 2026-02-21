<?php

namespace Georgeff\Problem\Contract;

interface ContextEnricher
{
    /**
     * @param mixed[] $context
     *
     * @return mixed[]
     */
    public function enrich(array $context): array;
}
