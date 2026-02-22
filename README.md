# georgeff/problem

Structured domain exceptions for PHP with a fluent builder API, exception translation, severity levels, context, correlation tracking, and serialization.

## Installation

```bash
composer require georgeff/problem
```

## Overview

This package provides a set of domain-scoped exception classes that extend PHP's `\Exception` with structured fields for logging, translation, and observability:

- **Severity** — one of `critical`, `error`, `warning`, `info`, `debug`
- **Context** — arbitrary key-value data describing the failure scenario
- **Retryability** — signals whether the triggering operation can be retried
- **Correlation ID** — request-scoped trace identifier, `null` when not provided
- **Structured data** — a fully serializable snapshot for loggers and reporters

Each exception type comes with a fluent **builder** that handles message generation, code assignment, and context population for common failure scenarios. A **translator** converts arbitrary PHP exceptions into typed domain exceptions via a priority-ordered handler pipeline. A **reporter** composes translation and structured logging into a single drop-in component for application exception handlers.

## Documentation

### Core

- [DomainException](.docs/domain-exception.md) — abstract base class for all domain exceptions
- [ExceptionBuilder](.docs/exception-builder.md) — generic fluent builder; base for all domain-specific builders

### Translation

- [Translation](.docs/translation.md) — `ExceptionTranslator`, handler pattern, `PDOCatchAllHandler`, and SQLSTATE routing

### Logging

- [Logging](.docs/logging.md) — `JsonStructuredLogger`, `EnvironmentEnricher`, `ContextEnricher`, and combining with translation

### Reporting

- [Reporting](.docs/reporting.md) — `ExceptionReporter`, correlation ID threading, application handler integration

### Exceptions & Builders

| Exception                  | Severity   | Retryable | Error Code       | Docs                                             |
| -------------------------- | ---------- | --------- | ---------------- | ------------------------------------------------ |
| `DatabaseException`        | `error`    | `false`   | `DB_NNNN`        | [database.md](.docs/database.md)                 |
| `ValidationException`      | `warning`  | `false`   | `VAL_NNNN`       | [validation.md](.docs/validation.md)             |
| `ExternalServiceException` | `error`    | `true`    | `EXT_NNNN`       | [external-service.md](.docs/external-service.md) |
| `AuthenticationException`  | `warning`  | `true`    | `AUTHN_NNNN`     | [authentication.md](.docs/authentication.md)     |
| `AuthorizationException`   | `warning`  | `false`   | `AUTHZ_NNNN`     | [authorization.md](.docs/authorization.md)       |
| `NotFoundException`        | `info`     | `false`   | `NOT_FOUND_NNNN` | [not-found.md](.docs/not-found.md)               |
| `ConfigurationException`   | `critical` | `false`   | `CFG_NNNN`       | [configuration.md](.docs/configuration.md)       |
| `UnknownException`         | `error`    | `false`   | `UNKNOWN_0000`   | [unknown.md](.docs/unknown.md)                   |

## Quick Example

```php
use Georgeff\Problem\DatabaseExceptionBuilder;
use Georgeff\Problem\ValidationExceptionBuilder;
use Georgeff\Problem\Exception\DomainException;

// Database failure
DatabaseExceptionBuilder::new()
    ->connectionFailed()
    ->connection('db.example.com', 5432, 'myapp')
    ->throw();

// Validation failure
$builder = ValidationExceptionBuilder::new()
    ->required('email')
    ->minLength('username', 'ab', 3);

if ($builder->hasErrors()) {
    $builder->throw();
}

// Inspect any domain exception
} catch (DomainException $e) {
    $e->severity;        // "error"
    $e->retryable;       // false
    $e->context;         // ['query' => [...], ...]
    $e->correlationId;   // "a3f9..."
    $e->structuredData;  // full serializable snapshot
    $e->getErrorCode();  // "DB_0001"
}
```

## License

MIT — see [LICENSE](LICENSE).
