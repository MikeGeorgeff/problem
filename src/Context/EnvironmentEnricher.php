<?php

namespace Georgeff\Problem\Context;

use Georgeff\Problem\Contract\ContextEnricher;

final class EnvironmentEnricher implements ContextEnricher
{
    /**
     * @param string[] $vars
     */
    public function __construct(private readonly array $vars = ['APP_ENV', 'APP_NAME', 'APP_VERSION']) {}

    public function enrich(array $context): array
    {
        $env = [];

        foreach ($this->vars as $var) {
            $value = getenv($var);

            if (false !== $value) {
                $env[$var] = $value;
            }
        }

        return $context + [
            'hostname'    => gethostname() ?: 'unknown',
            'pid'         => (int) getmypid(),
            'environment' => $env,
        ];
    }
}
