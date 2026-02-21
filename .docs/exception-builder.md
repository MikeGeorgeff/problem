# ExceptionBuilder

`Georgeff\Problem\ExceptionBuilder`

Generic fluent builder for constructing any `DomainException` subclass. All domain-specific builders extend this class. It manages the common fields — message, severity, context, retryability, code, correlation ID, and previous exception — and assembles the target exception via constructor reflection.

## Creating a Builder

### `ExceptionBuilder::exception(string $exceptionClass): static`

Static factory method. Accepts the fully-qualified class name of any `DomainException` subclass. Throws `InvalidArgumentException` if the class does not extend `DomainException`.

```php
$builder = ExceptionBuilder::exception(DatabaseException::class);
```

Prefer using domain-specific builders (e.g. `DatabaseExceptionBuilder::new()`) over this directly.

## Fluent Methods

All methods return `static` and can be chained.

### Message

```php
->message(string $message): static
```

Sets the exception message. Required — `build()` throws `LogicException` if no message is set.

### Severity

```php
->debug(): static
->info(): static
->warning(): static
->error(): static
->critical(): static
```

Sets the severity level. The default is `error`.

### Context

```php
->context(array $context): static
```

Merges an array into the current context.

```php
->with(string $key, mixed $value): static
```

Sets a single context key.

### Retryability

```php
->retryable(): static
->notRetryable(): static
```

Controls whether the exception signals that the operation can be retried.

### Other Fields

```php
->code(int $code): static
->correlationId(string $id): static
->previous(Throwable $previous): static
```

## Building

### `build(): DomainException`

Constructs and returns the configured exception. Throws `LogicException` if no message has been set.

### `throw(): never`

Builds and immediately throws the exception. Equivalent to `throw $builder->build()`.

## Protected Helpers

### `hasMessage(): bool`

Returns `true` if a message has been set. Available to subclasses to conditionally generate a default message before delegating to `parent::build()`.

## Subclassing

Domain-specific builders extend `ExceptionBuilder` and typically:

1. Define a `::new(): self` static factory that calls `new self(SpecificException::class)` and applies defaults.
2. Add scenario methods that call `->message()`, `->code()`, and `->with()` to configure a specific error condition.
3. Optionally override `build()` to validate required fields and auto-generate messages before calling `parent::build()`.

```php
final class DatabaseExceptionBuilder extends ExceptionBuilder
{
    public static function new(): self
    {
        return new self(DatabaseException::class);
    }

    public function deadlock(): self
    {
        return $this->message('Database deadlock detected')
                    ->code(2)
                    ->retryable();
    }

    // ...
}
```
