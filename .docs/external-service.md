# External Service

## ExternalServiceException

`Georgeff\Problem\Exception\ExternalServiceException`

Represents a failure when communicating with a third-party or downstream HTTP service. Extends `DomainException`.

| Default | Value |
|---|---|
| Severity | `error` |
| Retryable | `true` |
| Error code prefix | `EXT_` |

**Error code format:** `EXT_NNNN` (e.g. `EXT_0001`)

## ExternalServiceExceptionBuilder

`Georgeff\Problem\ExternalServiceExceptionBuilder`

Fluent builder for `ExternalServiceException`. The service name must be set via `->service()` before calling `build()` — otherwise a `LogicException` is thrown.

### Factory

```php
ExternalServiceExceptionBuilder::new(): self
```

Returns a new builder pre-configured for `ExternalServiceException` with `retryable = true` and severity `error`.

### Required Field

#### `service(string $service): self`

Sets the name of the external service. This value is included in all scenario messages and stored in context under the key `service`.

```php
ExternalServiceExceptionBuilder::new()->service('stripe');
```

### Scenario Methods

#### `timeout(int $seconds): self`

Code `1` — retryable. Adds `timeout_seconds` to context.

```
External service [<service>] timed out after <N>s
```

#### `unavailable(): self`

Code `2` — retryable.

```
External service [<service>] is unavailable
```

#### `authentication(): self`

Code `3` — retryable.

```
External service [<service>] authentication failed
```

#### `rateLimited(int $retryAfterSeconds): self`

Code `4` — retryable. Adds `retry_after_seconds` to context.

```
External service [<service>] rate limit exceeded
```

#### `connectionFailed(string $reason = ''): self`

Code `5` — retryable. Adds `connection_failure_reason` to context if a reason is provided.

```
External service [<service>] connection failed
```

#### `invalidResponse(): self`

Code `6` — **not retryable**. Use when the service returned a response that cannot be parsed or does not match the expected contract.

```
External service [<service>] returned an invalid response
```

### Context Helpers

#### `endpoint(string $method, string $uri): self`

Adds `method` (uppercased) and `uri` to context.

#### `statusCode(int $status): self`

Adds `http_status` to context.

#### `responseBody(string $body): self`

Adds `response_body` to context.

#### `responseTime(float $milliseconds): self`

Adds `response_time_ms` to context.

### Example

```php
throw ExternalServiceExceptionBuilder::new()
    ->service('stripe')
    ->timeout(5)
    ->endpoint('POST', '/v1/charges')
    ->responseTime(5043.2)
    ->throw();

throw ExternalServiceExceptionBuilder::new()
    ->service('sendgrid')
    ->invalidResponse()
    ->statusCode(200)
    ->responseBody('<!DOCTYPE html>...')
    ->throw();
```
