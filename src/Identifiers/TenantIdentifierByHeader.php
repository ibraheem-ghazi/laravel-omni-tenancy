<?php

namespace IbraheemGhazi\OmniTenancy\Identifiers;

use IbraheemGhazi\OmniTenancy\Contracts\TenantIdentifierMethod;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

class TenantIdentifierByHeader implements TenantIdentifierMethod
{
    protected array|Collection|null $config = null;

    public function setConfig(array|Collection|null $config = null): static
    {
        $this->config = $config;
        return $this;
    }

    public function identify(Request $request): ?TenantObject
    {
        $headerName = data_get($this->config, 'name', 'X-Tenant-Identifier');
        $headerValue = $request->header($headerName);

        return $this->identifyByValue($headerValue);
    }

    private function identifyByValue(null|string|Stringable $headerValue): ?TenantObject
    {
        if (!$headerValue) {
            return null;
        }

        $tenantId = $this->getDomainTenantIdMapping($headerValue);
        if ($tenantId && ($tenant = Tenancy::registry()->getTenant($tenantId))) {
            return $tenant;
        }

        if ($tenant = Tenancy::registry()->getTenant($headerValue)) {
            return $tenant;
        }

        session()->put('tenant_identifier', $headerValue);
        return null;
    }

    public function getDomainTenantIdMapping(string $fullHost): mixed
    {
        return data_get($this->config, "mapping.$fullHost");
    }

     public function isHostForbidden(string $fullHost): bool
    {
        return in_array($fullHost, data_get($this->config, 'excluded', []));
    }

    public function getFullHostFromInput(string $input): ?string
    {
        $tenantObject = $this->identifyByValue($input);
        return $tenantObject?->getRegisteredMainDomain(); //is it causing inf-loop ?
    }


}
