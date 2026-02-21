<?php

namespace Georgeff\Problem;

use Georgeff\Problem\Exception\NotFoundException;

final class NotFoundExceptionBuilder extends ExceptionBuilder
{
    public static function new(): self
    {
        return new self(NotFoundException::class)->notRetryable()->info();
    }

    public function resource(string $type, int|string|null $identifier = null): self
    {
        return $this->code(1)
                    ->message("Resource [{$type}] not found")
                    ->with('resource', $type)
                    ->with('resource_id', $identifier);
    }

    public function record(string $type, int|string $id): self
    {
        return $this->code(2)
                    ->message("Record [{$type}] with identifier [{$id}] not found")
                    ->with('record', $type)
                    ->with('record_id', $id);
    }

    public function endpoint(string $path, string $method): self
    {
        $method = strtoupper($method);

        return $this->code(3)
                    ->message("Endpoint not found: [{$method}] [{$path}]")
                    ->with('endpoint', $path)
                    ->with('method', $method);
    }

    public function file(string $path): self
    {
        return $this->code(4)
                    ->message("File not found: [{$path}]")
                    ->with('file', $path);
    }
}
