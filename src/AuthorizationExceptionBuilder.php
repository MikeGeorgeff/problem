<?php

namespace Georgeff\Problem;

use LogicException;
use Georgeff\Problem\Exception\DomainException;
use Georgeff\Problem\Exception\AuthorizationException;

final class AuthorizationExceptionBuilder extends ExceptionBuilder
{
    private string $resource = '';

    private string $action = '';

    public static function new(): self
    {
        return new self(AuthorizationException::class)->notRetryable()->warning();
    }

    public function on(string $resource, string $action): self
    {
        $this->resource = $resource;
        $this->action   = $action;

        return $this->with('authz_resource', $this->resource)
                    ->with('authz_action', $this->action);
    }

    /**
     * @param string[] $scopes
     */
    public function scopes(array $scopes): self
    {
        return $this->with('authz_scopes', $scopes);
    }

    /**
     * @param string[] $permissions
     */
    public function permissions(array $permissions): self
    {
        return $this->with('authz_permissions', $permissions);
    }

    public function forbidden(): self
    {
        return $this->code(1)
                    ->message("Forbidden: [{$this->action}] on [{$this->resource}]");
    }

    public function insufficientScope(): self
    {
        return $this->code(2)
                    ->message("Insufficient Scope: [{$this->action}] on [{$this->resource}]");
    }

    public function tokenPermissions(): self
    {
        return $this->code(3)
                    ->message("Token Lacks Permissions: [{$this->action}] on [{$this->resource}]");
    }

    public function build(): DomainException
    {
        if ('' === $this->resource || '' === $this->action) {
            throw new LogicException('Resource and action must be set');
        }

        return parent::build();
    }
}
