# Unknown

## UnknownException

`Georgeff\Problem\Exception\UnknownException`

A `final` wrapper for arbitrary `Throwable` instances that do not match any known domain exception pattern. Intended for use in the translator as a fallback when no specific mapping applies.

| Default | Value |
|---|---|
| Severity | `error` |
| Retryable | `false` |
| Error code | `UNKNOWN_0000` (fixed) |

## Constructor

```php
public function __construct(Throwable $exception, ?string $correlationId = null)
```

The message, code, and previous exception are taken directly from the wrapped `Throwable`. The following keys are captured in context:

| Key | Source |
|---|---|
| `original_class` | `$exception::class` |
| `original_code` | `$exception->getCode()` |
| `original_file` | `$exception->getFile()` |
| `original_line` | `$exception->getLine()` |

## Error Code

Always returns `UNKNOWN_0000`. The numeric code from the wrapped exception is still accessible via `getCode()`.

## Usage

`UnknownException` is not constructed via a builder. It is typically instantiated directly in a translator catch-all:

```php
try {
    // ...
} catch (Throwable $e) {
    throw new UnknownException($e, correlationId: $requestId);
}
```

Do not use `UnknownException` to handle expected failures — create a specific domain exception instead.
