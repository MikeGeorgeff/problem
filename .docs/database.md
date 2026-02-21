# Database

## DatabaseException

`Georgeff\Problem\Exception\DatabaseException`

Represents a failure originating from database interaction. Extends `DomainException`.

| Default | Value |
|---|---|
| Severity | `error` |
| Retryable | `false` |
| Error code prefix | `DB_` |

**Error code format:** `DB_NNNN` (e.g. `DB_0001`)

## DatabaseExceptionBuilder

`Georgeff\Problem\DatabaseExceptionBuilder`

Fluent builder for `DatabaseException`. Create via `DatabaseExceptionBuilder::new()`.

### Factory

```php
DatabaseExceptionBuilder::new(): self
```

Returns a new builder pre-configured for `DatabaseException` with default severity `error`.

### Scenario Methods

These methods set the message and code for a specific failure scenario and configure retryability.

#### `connectionFailed(string $reason = ''): self`

Code `1` — retryable. Appends the reason to the message if provided.

```
Database connection failed
Database connection failed: <reason>
```

#### `deadlock(): self`

Code `2` — retryable.

```
Database deadlock detected
```

#### `constraintViolation(string $constraint): self`

Code `3` — not retryable. Also calls `->constraint($constraint)`.

```
Database constraint violation: <constraint>
```

#### `timeout(int $seconds): self`

Code `4` — retryable. Adds `timeout_seconds` to context.

```
Database query timed out after <N>s
```

#### `transaction(string $operation = 'commit'): self`

Code `5` — retryable. Adds `transaction_operation` to context.

```
Database transaction <operation> failed
```

### Context Helpers

These methods attach structured data to the exception context and can be chained alongside scenario methods.

#### `query(string $sql, array $parameters = []): self`

Adds `query` to context as `['sql' => ..., 'params' => ...]`.

#### `connection(string $host, int $port, string $database): self`

Adds `connection` to context as `['host' => ..., 'port' => ..., 'database' => ...]`.

#### `table(string $table): self`

Adds `table` to context.

#### `constraint(string $constraint): self`

Adds `constraint` to context. Called automatically by `constraintViolation()`.

### Example

```php
throw DatabaseExceptionBuilder::new()
    ->connectionFailed('timed out')
    ->connection('db.example.com', 5432, 'myapp')
    ->throw();

throw DatabaseExceptionBuilder::new()
    ->constraintViolation('users_email_unique')
    ->table('users')
    ->throw();

throw DatabaseExceptionBuilder::new()
    ->timeout(30)
    ->query('SELECT * FROM orders WHERE status = ?', ['pending'])
    ->throw();
```
