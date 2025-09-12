<?php

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Support\Str;

//todo base_url, global_asset

if (! function_exists('tenancy')) {
    function tenancy()
    {
        static $instance = new Tenancy;
        return $instance;
    }
}
if (! function_exists('tenant_asset')) {
    /**
     * Generate an asset path based on current tenant context.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function tenant_asset(string $path, bool $secure = null, int|string|TenantObject $tenant = null): string
    {

        if(filled($tenant) && !$tenant instanceof TenantObject){
            $tenant = Tenancy::registry()->getTenant($tenant);
        }

        if(!$tenant){
            $tenant = Tenancy::context()->getCurrentTenant();
        }

        $prefix = 'assets/global/';
        if($tenant){
            $prefix = 'assets/' . crc32($tenant->getId()) . '/';
        }
        $url = asset($prefix . ltrim($path, '/'), $secure);
        return tenant_url($url, tenant: $tenant);
    }
}

if (! function_exists('tenant_url')) {
    /**
     * Generate a url based on current tenant context or a given tenant.
     *
     * @param string|null $path
     * @param array $parameters
     * @param bool|null $secure
     * @param int|string|TenantObject|null $tenant
     * @return string
     */
    function tenant_url(?string $path = null, array $parameters = [], ?bool $secure = null, int|string|TenantObject $tenant = null): string
    {
        if(filled($tenant) && !$tenant instanceof TenantObject){
            $tenant = Tenancy::registry()->getTenant($tenant);
        }
        if(!$tenant){
            $tenant = Tenancy::context()->getCurrentTenant();
        }

        $url = url($path ?: '/', $parameters, $secure);
        if(!$tenant){
            return $url;
        }
        $schema = parse_url($url, PHP_URL_SCHEME);
        $currentHost = TenantIdentifier::normalizeDomain($url, $schema);
        $tenantHost = $tenant->getMainDomain();
        if(!$tenantHost){
            return $url;
        }
        $newHost = TenantIdentifier::normalizeDomain($tenantHost, $schema);
        return Str::of($url)->replaceFirst($currentHost, $newHost)->toString();
    }
}

if(!function_exists('tenant_route')){
    function tenant_route($name, $parameters = [], $absolute = true, int|string|TenantObject $tenant = null): string
    {
        $url = route($name, $parameters, $absolute);
        return tenant_url($url, tenant: $tenant);
    }
}
