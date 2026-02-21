<?php

namespace Georgeff\Problem;

use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\ValidationException;

final class ValidationExceptionBuilder extends ExceptionBuilder
{
    /**
     * @var array<int, array{field: string, rule: string, value: mixed}>
     */
    private array $errors = [];

    public static function new(): self
    {
        return new self(ValidationException::class)->warning();
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }

    /**
     * @return array<int, array{field: string, rule: string, value: mixed}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function field(string $field, string $rule, mixed $value = null): self
    {
        $this->errors[] = [
            'field' => $field,
            'rule'  => $rule,
            'value' => $value,
        ];

        return $this->with('errors', $this->errors);
    }

    public function required(string $field): self
    {
        return $this->field($field, 'required');
    }

    /**
     * String length validation
     */
    public function minLength(string $field, mixed $value, int $min): self
    {
        return $this->field($field, 'min_length', $value)
                    ->with("{$field}_min_length", $min);
    }

    /**
     * String length validation
     */
    public function maxLength(string $field, mixed $value, int $max): self
    {
        return $this->field($field, 'max_length', $value)
                    ->with("{$field}_max_length", $max);
    }

    public function email(string $field, mixed $value): self
    {
        return $this->field($field, 'email', $value);
    }

    public function url(string $field, mixed $value): self
    {
        return $this->field($field, 'url', $value);
    }

    public function regex(string $field, mixed $value, string $pattern): self
    {
        return $this->field($field, 'regex', $value)
                    ->with("{$field}_regex_pattern", $pattern);
    }

    public function numeric(string $field, mixed $value): self
    {
        return $this->field($field, 'numeric', $value);
    }

    public function integer(string $field, mixed $value): self
    {
        return $this->field($field, 'integer', $value);
    }

    public function min(string $field, mixed $value, int|float $min): self
    {
        return $this->field($field, 'min', $value)
                    ->with("{$field}_min", $min);
    }

    public function max(string $field, mixed $value, int|float $max): self
    {
        return $this->field($field, 'max', $value)
                    ->with("{$field}_max", $max);
    }

    public function between(string $field, mixed $value, int|float $min, int|float $max): self
    {
        return $this->field($field, 'between', $value)
                    ->with("{$field}_min", $min)
                    ->with("{$field}_max", $max);
    }

    public function boolean(string $field, mixed $value): self
    {
        return $this->field($field, 'boolean', $value);
    }

    public function date(string $field, mixed $value, string $format): self
    {
        return $this->field($field, 'date', $value)
                    ->with("{$field}_date_format", $format);
    }

    public function array(string $field, mixed $value): self
    {
        return $this->field($field, 'array', $value);
    }

    /**
     * @param array<int, mixed> $allowed
     */
    public function in(string $field, mixed $value, array $allowed): self
    {
        return $this->field($field, 'in', $value)
                    ->with("{$field}_allowed", $allowed);
    }

    /**
     * @param array<int, mixed> $disallowed
     */
    public function notIn(string $field, mixed $value, array $disallowed): self
    {
        return $this->field($field, 'notIn', $value)
                    ->with("{$field}_disallowed", $disallowed);
    }

    public function build(): DomainException
    {
        if (!$this->hasMessage()) {
            $count = count($this->errors);

            match ($count) {
                0       => $this->message('Validation Error'),
                1       => $this->message("Validation failed for field [{$this->errors[0]['field']}]"),
                default => $this->message("Validation failed for {$count} field(s)")
            };
        }

        return parent::build();
    }
}
