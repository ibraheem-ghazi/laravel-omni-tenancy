<?php

namespace IbraheemGhazi\OmniTenancy\Contracts;

use Illuminate\Support\Collection;
use IbraheemGhazi\OmniTenancy\Models\TenantRequest;

interface TenantCreator
{
    public static function make(): static;
    public function fromRequest(int|string|TenantRequest $tenantRequest): static;
    public function keepRequestAfterCreateTenant(bool $value = true): static;
    public function withRoutesCache(bool $value = true): static;
    public function withName(string $name): static;
    public function setActive(bool $active): static;
    public function withDomain(string $domain): static;
    public function withSubDomain(string $subDomain): static;
    public function withDatabase(): static;
    public function withCustomDatabase(string $database, string $username = null, string $password = null): static;

    /**
     * Note: the order of routes groups array affect the order of running migrations and seeders
     * @param array|Collection $groups
     * @return $this
     */
    public function withRoutesGroups(array|Collection $groups): static;
    public function withOptions(array $options): static;

    public function withOwnerInfo(array|Collection $ownerInfo): static;
    public function createRequest();
    public function createTenant(): ?TenantObject;
    public function hasAnyWithDomain(string $domain, bool $includeMapped = true): bool;
    public function hasTenantWithDomain(string $domain, bool $includeMapped = true): bool;
    public function hasRequestWithDomain(string $domain, bool $includeMapped = true): bool;
}
