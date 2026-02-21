# Configuration

## ConfigurationException

`Georgeff\Problem\Exception\ConfigurationException`

Represents a problem with application configuration — a missing key, an invalid value, or a type mismatch. Extends `DomainException`.

| Default | Value |
|---|---|
| Severity | `critical` |
| Retryable | `false` |
| Error code prefix | `CFG_` |

**Error code format:** `CFG_NNNN` (e.g. `CFG_0001`)

## ConfigurationExceptionBuilder

`Georgeff\Problem\ConfigurationExceptionBuilder`

Fluent builder for `ConfigurationException`. The configuration key must be set via `->key()` before calling `build()` — otherwise a `LogicException` is thrown.

### Factory

```php
ConfigurationExceptionBuilder::new(): self
```

Returns a new builder pre-configured for `ConfigurationException` with severity `critical` and `retryable = false`.

### Required Field

#### `key(string $key): self`

Sets the configuration key being referenced. Stored in context under `config_key` and included in all scenario messages.

```php
ConfigurationExceptionBuilder::new()->key('database.host');
```

### Scenario Methods

#### `missing(): self`

Code `1`. The required configuration key is absent.

```
Required config key [<key>] is missing
```

#### `invalid(mixed $value, string $reason): self`

Code `2`. The value at the key fails a validation rule. Adds `invalid_value` and `reason` to context.

```
Config key [<key>] is invalid
```

#### `invalidType(mixed $value, string $expected, string $actual): self`

Code `2`. Shorthand for a type mismatch. Delegates to `invalid()` with reason `Invalid type`, then adds `expected_type` and `provided_type` to context.

```
Config key [<key>] is invalid
```

Context will contain:

| Key | Description |
|---|---|
| `invalid_value` | The value that was found |
| `reason` | `Invalid type` |
| `expected_type` | The type that was expected (e.g. `int`) |
| `provided_type` | The type that was provided (e.g. `string`) |

### Example

```php
throw ConfigurationExceptionBuilder::new()
    ->key('app.name')
    ->missing()
    ->throw();

throw ConfigurationExceptionBuilder::new()
    ->key('database.port')
    ->invalid('not-a-number', 'Must be an integer')
    ->throw();

throw ConfigurationExceptionBuilder::new()
    ->key('database.port')
    ->invalidType('3306', 'int', 'string')
    ->throw();
```
