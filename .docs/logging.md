# Logging

The logging layer provides structured, JSON-formatted log output built on top of PSR-3.

## Contracts

### `StructuredLogger`

Extends PSR-3's `LoggerInterface` with one additional method:

```php
interface StructuredLogger extends LoggerInterface
{
    public function logException(DomainException $exception): void;
}
```

`logException` reads the exception's `severity`, `getMessage()`, and `structuredData` and writes a single log entry — no boilerplate required at the call site.

### `ContextEnricher`

Enrichers augment log context before each entry is written:

```php
interface ContextEnricher
{
    public function enrich(array $context): array;
}
```

Enrichers receive the current context array and return a new one. They should not overwrite existing keys — use `+` (union) rather than `array_merge` to let caller-supplied context win.

## `JsonStructuredLogger`

Writes newline-delimited JSON to any PHP resource (`STDOUT`, a file handle, `php://memory`, etc.).

```php
use Georgeff\Problem\Logging\JsonStructuredLogger;

$logger = new JsonStructuredLogger(
    output:   STDOUT,
    logLevel: 'warning',    // minimum level; entries below this are discarded
    // ...enrichers (variadic)
);
```

### Constructor

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$output` | `resource` | — | Writable PHP resource |
| `$logLevel` | `string` | `'debug'` | Minimum PSR-3 level (case-insensitive) |
| `...$enrichers` | `ContextEnricher` | — | Zero or more enrichers applied per entry |

Throws `InvalidArgumentException` if `$output` is not a resource or `$logLevel` is not a valid PSR-3 level.

### Log entry format

Each entry is a single JSON object followed by a newline:

```json
{
  "timestamp": "2026-02-21T14:23:01.000+00:00",
  "level":     "error",
  "severity":  "error",
  "message":   "Connection refused",
  "context":   { "host": "db.example.com" }
}
```

| Field | Description |
|---|---|
| `timestamp` | RFC 3339 extended (millisecond precision) |
| `level` | PSR-3 level string (`debug` … `emergency`) |
| `severity` | Collapsed severity for downstream routing (see table below) |
| `message` | Log message cast to string |
| `context` | Merged context after all enrichers have run |

### Severity map

PSR-3 has eight levels; many log aggregators use fewer buckets. The logger maps them as follows:

| PSR-3 level | Severity |
|---|---|
| `emergency` | `critical` |
| `alert` | `critical` |
| `critical` | `critical` |
| `error` | `error` |
| `warning` | `warning` |
| `notice` | `info` |
| `info` | `info` |
| `debug` | `debug` |

### Minimum level filtering

Entries whose level is below the configured minimum are discarded before enrichers run — no unnecessary work is done for filtered entries.

Levels in ascending order: `debug` → `info` → `notice` → `warning` → `error` → `critical` → `alert` → `emergency`.

```php
$logger = new JsonStructuredLogger(STDOUT, 'warning');

$logger->debug('ignored');    // discarded
$logger->info('ignored');     // discarded
$logger->notice('ignored');   // discarded
$logger->warning('logged');   // written
$logger->error('logged');     // written
```

### PSR-3 usage

All eight PSR-3 shorthand methods are available:

```php
$logger->debug('cache miss', ['key' => 'users:42']);
$logger->info('request received');
$logger->notice('deprecated endpoint called');
$logger->warning('slow query', ['duration_ms' => 1200]);
$logger->error('connection refused', ['host' => 'db.example.com']);
$logger->critical('payment processor down');
$logger->alert('disk at 95%');
$logger->emergency('service unreachable');
```

Or via the generic `log()` method which accepts any `string|Stringable` level:

```php
$logger->log('error', 'something failed', ['key' => 'value']);
```

`log()` throws `InvalidArgumentException` for non-string levels or unrecognised level strings.

### Logging domain exceptions

```php
use Georgeff\Problem\DatabaseExceptionBuilder;

$exception = DatabaseExceptionBuilder::new()
    ->connectionFailed()
    ->connection('db.example.com', 5432, 'myapp')
    ->build();

$logger->logException($exception);
```

This writes a single entry using the exception's `severity` as the PSR-3 level and its `structuredData` as the context:

```json
{
  "timestamp": "2026-02-21T14:23:01.000+00:00",
  "level":     "error",
  "severity":  "error",
  "message":   "Failed to connect to database",
  "context": {
    "error_code":  "DB_0001",
    "severity":    "error",
    "retryable":   false,
    "context":     { "host": "db.example.com", "port": 5432, "database": "myapp" },
    "metadata":    { "php_version": "8.4.0", "timestamp": "2026-02-21T14:23:01+00:00" }
  }
}
```

## `EnvironmentEnricher`

Adds runtime environment information to every log entry.

```php
use Georgeff\Problem\Context\EnvironmentEnricher;

$logger = new JsonStructuredLogger(STDOUT, 'debug', new EnvironmentEnricher());
```

### Fields added

| Field | Type | Description |
|---|---|---|
| `hostname` | `string` | Result of `gethostname()`, `'unknown'` on failure |
| `pid` | `int` | Current process ID |
| `environment` | `array` | Selected environment variables (only those that are set) |

### Environment variables

The default variable list is `['APP_ENV', 'APP_NAME', 'APP_VERSION']`. Pass a custom list to the constructor:

```php
new EnvironmentEnricher(['APP_ENV', 'APP_NAME', 'APP_VERSION', 'REGION'])
```

Unset variables are omitted from the `environment` array rather than included as `null` or `false`.

### Non-overwriting merge

`EnvironmentEnricher` uses `+` to merge, so it will not overwrite keys already present in the context. Caller-supplied context always wins.

### Example output

```json
{
  "timestamp":  "...",
  "level":      "info",
  "severity":   "info",
  "message":    "request received",
  "context": {
    "hostname":    "web-01.example.com",
    "pid":         12345,
    "environment": {
      "APP_ENV":     "production",
      "APP_NAME":    "myapp",
      "APP_VERSION": "1.4.2"
    }
  }
}
```

## Multiple enrichers

Pass multiple enrichers to the constructor — they run in the order supplied, and each one receives the output of the previous:

```php
$logger = new JsonStructuredLogger(
    STDOUT,
    'debug',
    new EnvironmentEnricher(),
    new RequestIdEnricher($requestId),
);
```

Because each enricher should use `+` (non-overwriting merge), the first enricher to set a key wins. Caller-supplied context beats all enrichers since it is the starting value passed into the pipeline.

## Writing to a file

```php
$handle = fopen('/var/log/app.log', 'a');
$logger = new JsonStructuredLogger($handle, 'info', new EnvironmentEnricher());
```

## Combining translation and logging

```php
use Georgeff\Problem\Translation\ExceptionTranslator;
use Georgeff\Problem\Translation\PDOCatchAllHandler;
use Georgeff\Problem\Logging\JsonStructuredLogger;
use Georgeff\Problem\Context\EnvironmentEnricher;

$translator = new ExceptionTranslator();
PDOCatchAllHandler::register($translator);

$logger = new JsonStructuredLogger(STDOUT, 'warning', new EnvironmentEnricher());

try {
    $pdo->query($sql);
} catch (\Throwable $e) {
    $domain = $translator->translate($e, $correlationId);
    $logger->logException($domain);
}
```
