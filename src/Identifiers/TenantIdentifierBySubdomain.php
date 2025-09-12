<?php

namespace IbraheemGhazi\OmniTenancy\Identifiers;

use IbraheemGhazi\OmniTenancy\Contracts\TenantIdentifierMethod;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotFoundException;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TenantIdentifierBySubdomain implements TenantIdentifierMethod
{

    protected array|Collection|null $config = null;

    public function setConfig(array|Collection|null $config = null): static
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @throws TenantNotFoundException
     */
    public function identify(Request $request, array|Collection|null $config = null): ?TenantObject
    {
        $host = $request->getHost();
        $baseHost = config('app.base_url');
        $subdomain = $this->getSubdomainFromFullDomain($host);
        $fullHost = implode('.', [$subdomain, TenantIdentifier::normalizeDomain($baseHost)]);

        $tenantId  = $this->getDomainTenantIdMapping($host);

        if ($tenantId && ($tenant = Tenancy::registry()->getTenant($tenantId))) {
            return $tenant;
        }

        $tenant = Tenancy::registry()->getTenantByDomain($fullHost);

        if($tenant){
            return $tenant;
        }

        session()->put('tenant_identifier', $fullHost);
        return null;
    }

    protected function getSubdomainFromFullDomain(string $fullHost): ?string
    {
        $parts = explode('.', $fullHost);


        if (str_ends_with($fullHost, 'localhost') && count($parts) < 2) {
            return null;
        }

        if (!str_ends_with($fullHost, 'localhost') && count($parts) < 3) {
            return null;
        }

        return $parts[0];
    }

    public function getDomainTenantIdMapping(string $fullHost): mixed
    {
        $subdomain = $this->getSubdomainFromFullDomain($fullHost);
        return data_get($this->config, "mapping.$subdomain");
    }

    public function isHostForbidden(string $fullHost): bool
    {
        $subdomain = $this->getSubdomainFromFullDomain($fullHost);
        return in_array($subdomain, data_get($this->config, 'excluded', []));
    }

    public function getFullHostFromInput(string $input): ?string
    {
        $baseHost = config('app.base_url');
        $normalizedBaseHost = TenantIdentifier::normalizeDomain($baseHost);
        return "$input.$normalizedBaseHost";
    }


}
