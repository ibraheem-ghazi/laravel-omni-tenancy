<?php

namespace IbraheemGhazi\OmniTenancy\Identifiers;

use IbraheemGhazi\OmniTenancy\Contracts\TenantIdentifierMethod;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TenantIdentifierByDomain implements TenantIdentifierMethod
{
    protected array|Collection|null $config = null;
    public function setConfig(array|Collection|null $config = null): static
    {
        $this->config = $config;
        return $this;
    }

    public function identify(Request $request): ?TenantObject
    {
        $host = TenantIdentifier::normalizeDomain($request->getHost());

        $tenantId = $this->getDomainTenantIdMapping($host);

        if (filled($tenantId) && ($tenant = Tenancy::registry()->getTenant($tenantId))) {
            return $tenant;
        }

        $tenant = Tenancy::registry()->getTenantByDomain($host);

        if($tenant){
            return $tenant;
        }

        session()->put('tenant_identifier', $host);
        return null;
    }

    public function getDomainTenantIdMapping(string $fullHost): mixed
    {
        return collect(data_get($this->config, 'mapping'))->first(function ($_, $key) use($fullHost) {
            return TenantIdentifier::normalizeDomain($key) === $fullHost;
        });
    }

    public function isHostForbidden(string $fullHost): bool
    {
        return in_array($fullHost, data_get($this->config, 'excluded', []));
    }

    public function getFullHostFromInput(string $input): ?string
    {
        return TenantIdentifier::normalizeDomain($input);
    }
}
