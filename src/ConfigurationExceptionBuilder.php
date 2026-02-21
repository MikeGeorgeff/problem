<?php

namespace Georgeff\Problem;

use LogicException;
use Georgeff\Problem\Exception\ConfigurationException;
use Georgeff\Problem\Exception\DomainException;

final class ConfigurationExceptionBuilder extends ExceptionBuilder
{
    private string $key = '';

    public static function new(): self
    {
        return new self(ConfigurationException::class)->critical()->notRetryable();
    }

    public function key(string $key): self
    {
        $this->key = $key;

        return $this->with('config_key', $key);
    }

    public function missing(): self
    {
        return $this->code(1)
                    ->message("Required config key [{$this->key}] is missing");
    }

    public function invalid(mixed $value, string $reason): self
    {
        return $this->code(2)
                    ->message("Config key [{$this->key}] is invalid")
                    ->with('invalid_value', $value)
                    ->with('reason', $reason);
    }

    public function invalidType(mixed $value, string $expected, string $actual): self
    {
        return $this->invalid($value, 'Invalid type')
                    ->with('expected_type', $expected)
                    ->with('provided_type', $actual);
    }

    public function build(): DomainException
    {
        if ('' === $this->key) {
            throw new LogicException('Key must be set');
        }

        return parent::build();
    }
}
