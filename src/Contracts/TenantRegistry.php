<?php

namespace IbraheemGhazi\OmniTenancy\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TenantRegistry
{

    public function getTenant(string $id): ?TenantObject;
    public function getTenantByColumn(string $column, mixed $value): ?TenantObject;
    public function getTenantByDomain(string $domain): ?TenantObject;
    public function existsTenantByDomain(string $domain): bool;
    public function existsTenantByName(string $name): bool;

    /**
     * @return Collection<TenantObject>
     */
    public function getAllTenants(): Collection;
    public function getAllTenantsPaginated(int $page = 1, ?int $perpage = null): LengthAwarePaginator;
    public function getTenantIds(): array;

    /**
     * Iterate through tenants in batches, calling the callback for each tenant
     *
     * @param int $perPage Number of tenants to process per batch
     * @param callable $callback Callback function that receives each TenantObject
     * @param bool $activeOnly Whether to only process active tenants
     * @return void
     */
    public function forEachTenant(int $perPage, callable $callback, bool $activeOnly = false): void;

    public function newCreator(): TenantCreator;
}
