# Validation

## ValidationException

`Georgeff\Problem\Exception\ValidationException`

Represents one or more user input validation failures. Extends `DomainException`.

| Default | Value |
|---|---|
| Severity | `warning` |
| Retryable | `false` |
| Error code prefix | `VAL_` |

**Error code format:** `VAL_NNNN` (e.g. `VAL_0000`)

## ValidationExceptionBuilder

`Georgeff\Problem\ValidationExceptionBuilder`

Fluent builder for `ValidationException`. Accumulates field errors and auto-generates a message when `build()` is called if no message has been set.

### Factory

```php
ValidationExceptionBuilder::new(): self
```

Returns a new builder pre-configured for `ValidationException` with severity `warning`.

### Error Accumulation

Errors are accumulated as an array of `{field, rule, value}` entries and stored in context under the key `errors`. Multiple validation failures can be collected before calling `build()`.

#### `field(string $field, string $rule, mixed $value = null): self`

Base method. Appends a single error entry. All validation methods delegate to this.

### Auto-Generated Message

If no message is set via `->message()`, `build()` generates one based on the error count:

| Error count | Generated message |
|---|---|
| 0 | `Validation Error` |
| 1 | `Validation failed for field [<field>]` |
| 2+ | `Validation failed for <N> field(s)` |

### Validation Rule Methods

Each method appends an error for the named field and rule, and some add rule-specific context keys.

#### Format / Type Rules

| Method | Rule | Extra context |
|---|---|---|
| `required(string $field)` | `required` | — |
| `email(string $field, mixed $value)` | `email` | — |
| `url(string $field, mixed $value)` | `url` | — |
| `regex(string $field, mixed $value, string $pattern)` | `regex` | `{field}_regex_pattern` |
| `boolean(string $field, mixed $value)` | `boolean` | — |
| `date(string $field, mixed $value, string $format)` | `date` | `{field}_date_format` |
| `array(string $field, mixed $value)` | `array` | — |
| `numeric(string $field, mixed $value)` | `numeric` | — |
| `integer(string $field, mixed $value)` | `integer` | — |

#### String Length Rules

| Method | Rule | Extra context |
|---|---|---|
| `minLength(string $field, mixed $value, int $min)` | `min_length` | `{field}_min_length` |
| `maxLength(string $field, mixed $value, int $max)` | `max_length` | `{field}_max_length` |

#### Numeric Range Rules

| Method | Rule | Extra context |
|---|---|---|
| `min(string $field, mixed $value, int\|float $min)` | `min` | `{field}_min` |
| `max(string $field, mixed $value, int\|float $max)` | `max` | `{field}_max` |
| `between(string $field, mixed $value, int\|float $min, int\|float $max)` | `between` | `{field}_min`, `{field}_max` |

#### Enumeration Rules

| Method | Rule | Extra context |
|---|---|---|
| `in(string $field, mixed $value, array $allowed)` | `in` | `{field}_allowed` |
| `notIn(string $field, mixed $value, array $disallowed)` | `notIn` | `{field}_disallowed` |

### Inspection Methods

```php
->hasErrors(): bool
->getErrors(): array  // array<int, array{field: string, rule: string, value: mixed}>
```

Useful for conditionally throwing only when errors exist.

### Example

```php
$builder = ValidationExceptionBuilder::new()
    ->required('email')
    ->minLength('username', 'ab', 3)
    ->between('age', 200, 0, 120);

if ($builder->hasErrors()) {
    $builder->throw();
}
```

Single-field shorthand:

```php
ValidationExceptionBuilder::new()
    ->email('email', 'not-an-email')
    ->throw();

// Message: "Validation failed for field [email]"
```
