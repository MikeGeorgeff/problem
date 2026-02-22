# Reporting

The reporting layer provides a single entry point that wires translation and logging together, suitable for use in the `report()` method of an application exception handler.

## Contract

```php
interface ExceptionReporter
{
    public function report(Throwable $exception, ?string $correlationId = null): DomainException;
}
```

The contract is intentionally minimal — one method, one responsibility. Implementing the interface allows future decorators (e.g. circuit breaker, rate limiting) to wrap the reporter without touching call sites.

## `ExceptionReporter`

`Georgeff\Problem\Reporting\ExceptionReporter` is the concrete implementation. It composes a `Translator` and a `StructuredLogger`:

```php
use Georgeff\Problem\Reporting\ExceptionReporter;
use Georgeff\Problem\Translation\ExceptionTranslator;
use Georgeff\Problem\Logging\JsonStructuredLogger;

$reporter = new ExceptionReporter(
    new ExceptionTranslator(),
    new JsonStructuredLogger(STDOUT, 'warning'),
);
```

### What `report()` does

1. Passes the exception and correlation ID to the translator — any registered handler runs; unmatched exceptions fall back to `UnknownException`; `DomainException` instances pass through unchanged
2. Logs the translated domain exception via `logException()` — uses the exception's severity as the PSR-3 level
3. Returns the translated `DomainException` — callers can inspect it, re-throw it, or ignore the return value

### Correlation ID

Pass the request-scoped correlation ID so it flows through to the translated exception and appears in the log entry:

```php
$reporter->report($exception, $request->correlationId());
```

If omitted, `correlationId` on the resulting domain exception will be `null`.

## Application exception handler

Drop the reporter into the `report()` method of your framework's exception handler:

```php
public function report(Throwable $exception): void
{
    $this->reporter->report($exception, $this->correlationId());
}
```

The reporter does not render responses — it only translates and logs. Response rendering remains the responsibility of the `render()` method, which can type-check against `DomainException` or its subtypes.

## Full wiring example

```php
use Georgeff\Problem\Reporting\ExceptionReporter;
use Georgeff\Problem\Translation\ExceptionTranslator;
use Georgeff\Problem\Translation\PDOCatchAllHandler;
use Georgeff\Problem\Logging\JsonStructuredLogger;
use Georgeff\Problem\Context\EnvironmentEnricher;

$translator = new ExceptionTranslator();
PDOCatchAllHandler::register($translator);

// register additional handlers...
$translator->register(
    fn($e) => $e instanceof GuzzleHttp\Exception\ConnectException,
    fn($e, $id) => ExternalServiceExceptionBuilder::new()
        ->connectionFailed('payment-api')
        ->correlationId($id)
        ->build()
);

$logger = new JsonStructuredLogger(STDOUT, 'warning', new EnvironmentEnricher());

$reporter = new ExceptionReporter($translator, $logger);
```

## Decorator pattern

Because `report()` is defined on the `ExceptionReporter` contract, decorators can wrap the reporter transparently:

```php
final class CircuitBreakingReporter implements ExceptionReporter
{
    public function __construct(
        private readonly ExceptionReporter $inner,
        // ...
    ) {}

    public function report(Throwable $exception, ?string $correlationId = null): DomainException
    {
        $domain = $this->inner->report($exception, $correlationId);

        // record failure against matching circuits...

        return $domain;
    }
}
```

Inject the decorator wherever `ExceptionReporter` is type-hinted — no call sites change.
