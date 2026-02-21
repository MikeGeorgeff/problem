<?php

namespace Georgeff\Problem;

use Georgeff\Problem\Exception\DatabaseException;

final class DatabaseExceptionBuilder extends ExceptionBuilder
{
    public static function new(): self
    {
        return new self(DatabaseException::class);
    }

    public function connectionFailed(string $reason = ''): self
    {
        $message = 'Database connection failed';

        if ('' !== $reason) {
            $message .= ": {$reason}";
        }

        return $this->message($message)
                    ->code(1)
                    ->retryable();
    }

    public function deadlock(): self
    {
        return $this->message('Database deadlock detected')
                    ->code(2)
                    ->retryable();
    }

    public function constraintViolation(string $constraint): self
    {
        return $this->message("Database constraint violation: {$constraint}")
                    ->code(3)
                    ->constraint($constraint)
                    ->notRetryable();
    }

    public function timeout(int $seconds): self
    {
        return $this->message("Database query timed out after {$seconds}s")
                    ->code(4)
                    ->with('timeout_seconds', $seconds)
                    ->retryable();
    }

    public function transaction(string $operation = 'commit'): self
    {
        return $this->message("Database transaction {$operation} failed")
                    ->code(5)
                    ->with('transaction_operation', $operation)
                    ->retryable();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function query(string $sql, array $parameters = []): self
    {
        return $this->with('query', ['sql' => $sql, 'params' => $parameters]);
    }

    public function connection(string $host, int $port, string $database): self
    {
        return $this->with('connection', ['host' => $host, 'port' => $port, 'database' => $database]);
    }

    public function table(string $table): self
    {
        return $this->with('table', $table);
    }

    public function constraint(string $constraint): self
    {
        return $this->with('constraint', $constraint);
    }
}
