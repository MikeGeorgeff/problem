<?php

namespace Georgeff\Problem;

use LogicException;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\ExternalServiceException;

final class ExternalServiceExceptionBuilder extends ExceptionBuilder
{
    private string $service = '';

    public static function new(): self
    {
        return new self(ExternalServiceException::class)->retryable();
    }

    public function service(string $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function endpoint(string $method, string $uri): self
    {
        return $this->with('method', strtoupper($method))
                    ->with('uri', $uri);
    }

    public function statusCode(int $status): self
    {
        return $this->with('http_status', $status);
    }

    public function responseBody(string $body): self
    {
        return $this->with('response_body', $body);
    }

    public function responseTime(float $milliseconds): self
    {
        return $this->with('response_time_ms', $milliseconds);
    }

    public function timeout(int $seconds): self
    {
        return $this->code(1)
                    ->message("External service [{$this->service}] timed out after {$seconds}s")
                    ->with('timeout_seconds', $seconds);
    }

    public function unavailable(): self
    {
        return $this->code(2)
                    ->message("External service [{$this->service}] is unavailable");
    }

    public function authentication(): self
    {
        return $this->code(3)
                    ->message("External service [{$this->service}] authentication failed");
    }

    public function rateLimited(int $retryAfterSeconds): self
    {
        return $this->code(4)
                    ->message("External service [{$this->service}] rate limit exceeded")
                    ->with('retry_after_seconds', $retryAfterSeconds);
    }

    public function connectionFailed(string $reason = ''): self
    {
        if ('' !== $reason) {
            $this->with('connection_failure_reason', $reason);
        }

        return $this->code(5)
                    ->message("External service [{$this->service}] connection failed");
    }

    public function invalidResponse(): self
    {
        return $this->code(6)
                    ->notRetryable()
                    ->message("External service [{$this->service}] returned an invalid response");
    }

    public function build(): DomainException
    {
        if ('' === $this->service) {
            throw new LogicException('Service name is required');
        }

        $this->with('service', $this->service);

        return parent::build();
    }
}
