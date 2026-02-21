# Not Found

## NotFoundException

`Georgeff\Problem\Exception\NotFoundException`

Represents a resource, record, endpoint, or file that could not be located. Extends `DomainException`.

| Default | Value |
|---|---|
| Severity | `info` |
| Retryable | `false` |
| Error code prefix | `NOT_FOUND_` |

**Error code format:** `NOT_FOUND_NNNN` (e.g. `NOT_FOUND_0001`)

## NotFoundExceptionBuilder

`Georgeff\Problem\NotFoundExceptionBuilder`

Fluent builder for `NotFoundException`. No required fields — any scenario method is sufficient to produce a valid exception.

### Factory

```php
NotFoundExceptionBuilder::new(): self
```

Returns a new builder pre-configured for `NotFoundException` with severity `info` and `retryable = false`.

### Scenario Methods

#### `resource(string $type, int|string|null $identifier = null): self`

Code `1`. Generic resource lookup. Adds `resource` and `resource_id` (may be `null`) to context.

```
Resource [<type>] not found
```

#### `record(string $type, int|string $id): self`

Code `2`. Database record lookup. Adds `record` and `record_id` to context.

```
Record [<type>] with identifier [<id>] not found
```

#### `endpoint(string $path, string $method): self`

Code `3`. Route not found. The method is uppercased. Adds `endpoint` and `method` to context.

```
Endpoint not found: [<METHOD>] [<path>]
```

#### `file(string $path): self`

Code `4`. Filesystem lookup. Adds `file` to context.

```
File not found: [<path>]
```

### Example

```php
throw NotFoundExceptionBuilder::new()
    ->resource('post', $slug)
    ->throw();

throw NotFoundExceptionBuilder::new()
    ->record('user', $id)
    ->throw();

throw NotFoundExceptionBuilder::new()
    ->endpoint('/api/v1/orders', 'GET')
    ->throw();

throw NotFoundExceptionBuilder::new()
    ->file('/var/app/storage/upload.csv')
    ->throw();
```
