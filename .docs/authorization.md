# Authorization

## AuthorizationException

`Georgeff\Problem\Exception\AuthorizationException`

Represents a failure to authorize an action on a resource, typically due to missing scopes or permissions on a system-level token. Extends `DomainException`.

| Default | Value |
|---|---|
| Severity | `warning` |
| Retryable | `false` |
| Error code prefix | `AUTHZ_` |

**Error code format:** `AUTHZ_NNNN` (e.g. `AUTHZ_0001`)

## AuthorizationExceptionBuilder

`Georgeff\Problem\AuthorizationExceptionBuilder`

Fluent builder for `AuthorizationException`. Both resource and action must be set via `->on()` before calling `build()` — otherwise a `LogicException` is thrown.

### Factory

```php
AuthorizationExceptionBuilder::new(): self
```

Returns a new builder pre-configured for `AuthorizationException` with severity `warning` and `retryable = false`.

### Required Field

#### `on(string $resource, string $action): self`

Sets the resource and action being attempted. Both are stored in context (`authz_resource`, `authz_action`) and included in all scenario messages.

```php
AuthorizationExceptionBuilder::new()->on('invoices', 'delete');
```

### Scenario Methods

#### `forbidden(): self`

Code `1`. General access denial.

```
Forbidden: [<action>] on [<resource>]
```

#### `insufficientScope(): self`

Code `2`. The token's OAuth scopes do not cover the requested action.

```
Insufficient Scope: [<action>] on [<resource>]
```

#### `tokenPermissions(): self`

Code `3`. The token lacks the required permissions.

```
Token Lacks Permissions: [<action>] on [<resource>]
```

### Context Helpers

#### `scopes(array $scopes): self`

Adds `authz_scopes` to context. Use to record the scopes present on the token.

#### `permissions(array $permissions): self`

Adds `authz_permissions` to context. Use to record the permissions present on the token.

### Example

```php
throw AuthorizationExceptionBuilder::new()
    ->on('invoices', 'delete')
    ->insufficientScope()
    ->scopes(['invoices:read'])
    ->throw();

throw AuthorizationExceptionBuilder::new()
    ->on('admin-panel', 'access')
    ->forbidden()
    ->throw();
```
