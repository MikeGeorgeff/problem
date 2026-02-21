# Translation

The translation layer converts arbitrary PHP exceptions into typed domain exceptions. It is built around the `Translator` contract, the `ExceptionTranslator` implementation, and a handler pattern that separates matching logic from conversion logic.

## Translator Contract

`Georgeff\Problem\Contract\Translator`

```php
interface Translator
{
    public function register(callable $pattern, callable $callback, int $priority = 0): static;
    public function translate(Throwable $exception, ?string $correlationId = null): DomainException;
}
```

## ExceptionTranslator

`Georgeff\Problem\Translation\ExceptionTranslator`

The concrete implementation. Mutable — handlers are registered at bootstrap time, then the translator is used read-only.

### `register(callable $pattern, callable $callback, int $priority = 0): static`

Registers a translation handler. Returns `$this` for chaining.

| Parameter | Type | Description |
|---|---|---|
| `$pattern` | `callable(Throwable): bool` | Predicate that returns `true` if this handler should handle the exception |
| `$callback` | `callable(Throwable, ?string): DomainException` | Converts the exception to a domain exception. Receives the original exception and the correlation ID. |
| `$priority` | `int` | Higher numbers run first. Default `0`. |

Handlers are sorted by descending priority on each registration. The first matching handler wins.

### `translate(Throwable $exception, ?string $correlationId = null): DomainException`

Translates an exception. Behaviour:

- If `$exception` is already a `DomainException`, it is returned as-is — the caller's correlation ID is ignored and the exception's own is preserved.
- Otherwise, handlers are checked in priority order. The first whose pattern returns `true` has its callback invoked.
- If no handler matches, an `UnknownException` is returned as the fallback.

The `$correlationId` is forwarded to both the handler callback and the `UnknownException` fallback. Pass your request-scoped trace ID here to ensure the resulting domain exception is tied to the correct request.

```php
$domain = $translator->translate($e, $request->correlationId());
```

## Writing a Handler

Handlers are plain classes with three responsibilities: matching, converting, and self-registering. The recommended pattern:

```php
final class MyHandler
{
    public static function register(Translator $translator): void
    {
        $self = new self();
        $translator->register(fn(Throwable $e) => $self->matches($e), $self, 10);
    }

    public function __invoke(Throwable $e, ?string $correlationId = null): DomainException
    {
        // convert $e to a domain exception
    }

    private function matches(Throwable $e): bool
    {
        return $e instanceof SomeException;
    }
}
```

Key points:

- `matches()` can be private — it is only called via the closure captured in `register()`
- `__invoke()` must accept `Throwable` as the first parameter (not a subtype) to satisfy the callable contract
- Use a `/** @var SpecificException $e */` assertion inside `__invoke()` when you need access to subtype-specific properties — safe because `matches()` guarantees the type
- Return type must be `DomainException` (not a subtype) for the same reason
- Set priority explicitly in `register()` so handler ordering is intentional

## PDOCatchAllHandler

`Georgeff\Problem\Translation\PDOCatchAllHandler`

A ready-made handler that translates any `\PDOException` into a `DatabaseException`. Registered at priority `0` — it is designed to be a fallback that more specific handlers (registered at higher priorities) can override.

### Registration

```php
PDOCatchAllHandler::register($translator);
```

### Produced exception

| Field | Value |
|---|---|
| Type | `DatabaseException` |
| Message | Taken directly from `PDOException::getMessage()` |
| Code | `6` |
| `previous` | The original `PDOException` |
| `context.original_code` | `PDOException::getCode()` |
| `context.original_file` | `PDOException::getFile()` |
| `context.original_line` | `PDOException::getLine()` |

### Registering more specific PDO handlers

Because `PDOCatchAllHandler` sits at priority `0`, you can register specific handlers at higher priorities to intercept known failure modes before the catchall fires. SQLSTATE codes are available via `$e->errorInfo[0]` and driver-specific codes via `$e->errorInfo[1]`.

```php
// Deadlock — SQLSTATE 40001 (MySQL + PostgreSQL)
$translator->register(
    fn(Throwable $e) => $e instanceof PDOException
        && isset($e->errorInfo)
        && '40001' === $e->errorInfo[0],
    fn(Throwable $e, ?string $id) => DatabaseExceptionBuilder::new()
        ->deadlock()
        ->correlationId($id)
        ->previous($e)
        ->build(),
    priority: 40
);

// Constraint violation — SQLSTATE 23xxx
$translator->register(
    fn(Throwable $e) => $e instanceof PDOException
        && isset($e->errorInfo)
        && str_starts_with((string) $e->errorInfo[0], '23'),
    fn(Throwable $e, ?string $id) => DatabaseExceptionBuilder::new()
        ->message('Database constraint violation')
        ->code(3)
        ->notRetryable()
        ->correlationId($id)
        ->previous($e)
        ->with('sqlstate', $e->errorInfo[0])
        ->with('driver_message', $e->errorInfo[2])
        ->build(),
    priority: 30
);

// Timeout — PostgreSQL SQLSTATE 57014, MySQL codes 1205 / 3024
$translator->register(
    fn(Throwable $e) => $e instanceof PDOException
        && isset($e->errorInfo)
        && (
            '57014' === $e->errorInfo[0]   // PostgreSQL statement_timeout
            || 1205 === $e->errorInfo[1]   // MySQL lock wait timeout
            || 3024 === $e->errorInfo[1]   // MySQL MAX_EXECUTION_TIME exceeded
        ),
    fn(Throwable $e, ?string $id) => DatabaseExceptionBuilder::new()
        ->message('Database query timed out')
        ->code(4)
        ->retryable()
        ->correlationId($id)
        ->previous($e)
        ->build(),
    priority: 20
);

PDOCatchAllHandler::register($translator); // priority 0 — catches anything else
```

## Full Example

```php
use Georgeff\Problem\Translation\ExceptionTranslator;
use Georgeff\Problem\Translation\PDOCatchAllHandler;

$translator = new ExceptionTranslator();

PDOCatchAllHandler::register($translator);

// In application code:
try {
    $db->query('SELECT ...');
} catch (Throwable $e) {
    $domain = $translator->translate($e, $request->correlationId());

    if ($domain->retryable) {
        // retry logic
    }

    throw $domain;
}
```
