# Authentication

## AuthenticationException

`Georgeff\Problem\Exception\AuthenticationException`

Represents a failure to verify the identity of a user via their token or credentials. Extends `DomainException`.

| Default | Value |
|---|---|
| Severity | `warning` |
| Retryable | `true` |
| Error code prefix | `AUTHN_` |

**Error code format:** `AUTHN_NNNN` (e.g. `AUTHN_0001`)

## AuthenticationExceptionBuilder

`Georgeff\Problem\AuthenticationExceptionBuilder`

Fluent builder for `AuthenticationException`. The user ID must be set via `->user()` before calling `build()` — otherwise a `LogicException` is thrown.

### Factory

```php
AuthenticationExceptionBuilder::new(): self
```

Returns a new builder pre-configured for `AuthenticationException` with severity `warning` and `retryable = true`.

### Required Field

#### `user(string $id): self`

Sets the user identifier. Stored in context under `user_id` and included in all scenario messages.

```php
AuthenticationExceptionBuilder::new()->user('user-abc-123');
```

### Scenario Methods

#### `tokenInvalid(): self`

Code `1`.

```
Authentication token for user [<id>] is invalid
```

#### `tokenExpired(): self`

Code `2`.

```
Authentication token for user [<id>] is expired
```

#### `credentialsInvalid(): self`

Code `3`.

```
Authentication credentials for user [<id>] are invalid
```

#### `sessionNotFound(): self`

Code `4`.

```
Authentication session for user [<id>] was not found
```

### Context Helpers

#### `tokenType(string $type): self`

Adds `authn_token_type` to context (e.g. `bearer`, `api_key`).

#### `tokenExpiry(DateTimeInterface $exp): self`

Adds `authn_token_exp` to context as an RFC3339 string.

### Example

```php
throw AuthenticationExceptionBuilder::new()
    ->user($userId)
    ->tokenExpired()
    ->tokenType('bearer')
    ->tokenExpiry($token->expiresAt())
    ->throw();

throw AuthenticationExceptionBuilder::new()
    ->user($userId)
    ->credentialsInvalid()
    ->throw();
```
