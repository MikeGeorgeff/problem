<?php

namespace Georgeff\Problem;

use LogicException;
use DateTimeInterface;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\AuthenticationException;

final class AuthenticationExceptionBuilder extends ExceptionBuilder
{
    private string $userId = '';

    public static function new(): self
    {
        return new self(AuthenticationException::class)->retryable()->warning();
    }

    public function user(string $id): self
    {
        $this->userId = $id;

        return $this->with('user_id', $id);
    }

    public function tokenType(string $type): self
    {
        return $this->with('authn_token_type', $type);
    }

    public function tokenExpiry(DateTimeInterface $exp): self
    {
        return $this->with('authn_token_exp', $exp->format(DateTimeInterface::RFC3339));
    }

    public function tokenInvalid(): self
    {
        return $this->code(1)
                    ->message("Authentication token for user [{$this->userId}] is invalid");
    }

    public function tokenExpired(): self
    {
        return $this->code(2)
                    ->message("Authentication token for user [{$this->userId}] is expired");
    }

    public function credentialsInvalid(): self
    {
        return $this->code(3)
                    ->message("Authentication credentials for user [{$this->userId}] are invalid");
    }

    public function sessionNotFound(): self
    {
        return $this->code(4)
                    ->message("Authentication session for user [{$this->userId}] was not found");
    }

    public function build(): DomainException
    {
        if ('' === $this->userId) {
            throw new LogicException('User ID must be set');
        }

        return parent::build();
    }
}
