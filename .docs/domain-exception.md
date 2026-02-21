# DomainException

`Georgeff\Problem\Exception\DomainException`

Abstract base class for all structured domain exceptions in this package. Extends PHP's built-in `\Exception` and adds structured fields for severity, context, correlation tracking, retry intent, and serialization.

## Properties

| Property | Type | Access | Description |
|---|---|---|---|
| `severity` | `string` | `public protected(set)` | Log severity level. One of: `critical`, `error`, `warning`, `info`, `debug`. Validated on set. |
| `context` | `array<string, mixed>` | `public protected(set)` | Arbitrary key-value data describing the error scenario. |
| `retryable` | `bool` | `public protected(set)` | Whether the operation that triggered this exception can be retried. |
| `correlationId` | `string` | `public protected(set)` | Request-scoped trace identifier. Auto-generated with `bin2hex(random_bytes(16))` if not provided. |
| `occurredAt` | `DateTimeImmutable` | `public protected(set)` | Timestamp captured at construction time. |
| `metadata` | `array<string, mixed>` | `public` (read-only hook) | Lazily computed. Contains `file`, `line`, `class`, and `php_version`. |
| `structuredData` | `array<string, mixed>` | `public` (read-only hook) | Full structured snapshot. See below. |

### `structuredData` shape

```php
[
    'error_code'     => string,   // e.g. "DB_0001"
    'message'        => string,
    'severity'       => string,
    'correlation_id' => string,
    'retryable'      => bool,
    'occurred_at'    => string,   // RFC3339
    'context'        => array,
    'metadata'       => array,
]
```

## Constructor

```php
public function __construct(
    string $message,
    string $severity,
    array $context = [],
    ?string $correlationId = null,
    bool $retryable = false,
    int $code = 0,
    ?Throwable $previous = null
)
```

The constructor is `final` — subclasses must call `parent::__construct()` and cannot change the signature.

## Abstract Method

```php
abstract public function getErrorCode(): string;
```

Each concrete exception class implements this to return a prefixed error code (e.g. `DB_0001`, `VAL_0002`).

## Protected Helpers

### `generateErrorCodeSuffix(int $code): string`

Formats an integer as a zero-padded four-digit string. Used by subclasses to implement `getErrorCode()`.

```php
$this->generateErrorCodeSuffix(1);  // "0001"
$this->generateErrorCodeSuffix(99); // "0099"
```

### `captureMetadata(): array`

Returns an array with `file`, `line`, `class`, and `php_version`. Called lazily on first access of `$metadata`.

## Severity Levels

Valid values for the `severity` property, ordered by descending urgency:

| Level | Description |
|---|---|
| `critical` | System is unusable; immediate action required |
| `error` | Runtime error that requires attention |
| `warning` | Unexpected condition; application continues |
| `info` | Noteworthy but expected event |
| `debug` | Diagnostic detail |

Passing an invalid severity throws `InvalidArgumentException`.
