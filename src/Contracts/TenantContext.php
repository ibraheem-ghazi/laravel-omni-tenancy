<?php

namespace IbraheemGhazi\OmniTenancy\Contracts;

interface TenantContext
{
    public function setTenant(string|int|TenantObject $id): void;
    public function usingTenant(TenantObject|int|string|null $id, callable $callback): mixed;
    public function refreshTenant(): void;
    public function reset(): void;
    public function getCurrentTenant(): ?TenantObject;
    public function hasTenant(): bool;
}
