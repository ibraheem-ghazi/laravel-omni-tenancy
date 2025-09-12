<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use IbraheemGhazi\OmniTenancy\Contracts\TenantIdentifierMethod;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class TenantIdentifier
{

    /**
     * @throws Throwable
     */
    public function identify(Request $request): ?TenantObject
    {
        foreach (static::resolveIdentificationMethods() as $instance){
            /** @var TenantIdentifierMethod $instance */
            $tenant = $instance->identify($request);
            if($tenant instanceof TenantObject){
                return $tenant;
            }
        }
       return $this->getFallbackTenant() ?? null;

    }

    /**
     * @return array<TenantIdentifierMethod>
     */
    public static function resolveIdentificationMethods(): array
    {
        $instances = [];
        foreach (static::getIdentificationMethods() as $class=>$config) {
            /** @var TenantIdentifierMethod $classInstance */
            $classInstance = resolve($class);
            if(!in_array(TenantIdentifierMethod::class, class_implements($classInstance))){
                throw new RuntimeException(
                    'tenant identifier method class "' . get_class($classInstance)
                    . '" does not implement ' . class_basename(TenantIdentifierMethod::class)
                );
            }
            $classInstance->setConfig($config);
            $instances[$class] = $classInstance;
            unset($class, $classInstance, $config);
        }
        return $instances;
    }

    private function getFallbackTenant(): ?TenantObject
    {
        $fallbackId = config('tenancy.identifications.fallback.tenant_id');
        return $fallbackId ? Tenancy::registry()->getTenant($fallbackId) : null;
    }


    protected static function getIdentificationMethods()
    {
        return config('tenancy.identifications.methods', []);
    }

    public static function normalizeDomain(?string $url, ?string $protocol = null): ?string
    {
        if(!$url){
            return null;
        }
        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if (!$parsedUrl) {
            $parsedUrl = ltrim($url, '/');
            if (str_contains($parsedUrl, '/')) {
                $parsedUrl = explode('/', $parsedUrl)[0];
            }
        }

        if(filled($parsedUrl) && filled($protocol)){
            return "$protocol://$parsedUrl";
        }

        return $parsedUrl;
    }

    public static function domain(?string $subdomain = null, ?string $domain = null): ?string
    {
        return implode('.', array_filter([
            $subdomain,
            TenantIdentifier::normalizeDomain($domain ?: config('app.base_url') ?: config('app.url'))
        ]));
    }

    public static function guessTenantDomain(null|int|string|TenantObject $tenant, ?string $protocol = null): ?string
    {
        if(!filled($protocol)){
            $protocol = parse_url(config('app.url'), PHP_URL_SCHEME) ?? null;
        }
        if(filled($tenant) && !$tenant instanceof TenantObject){
            $tenant = Tenancy::registry()->getTenant($tenant);
        }
        if(!$tenant){
            return null;
        }

        foreach (config('tenancy.identifications.methods') as $class=>$config) {
            if(!isset($config['mapping']) || !is_array($config['mapping'])) {
                continue;
            }
            foreach ($config['mapping'] as $domain=>$tenantId) {
                if(strval($tenant->getId()) !== strval($tenantId)){
                    continue;
                }
                if(method_exists($class, 'getFullHostFromInput')){
                    /** @var TenantIdentifierMethod $instance */
                    $instance = app($class);
                    if($output = $instance->getFullHostFromInput($domain)){
                        return static::normalizeDomain($output, $protocol);
                    }
                }
                if(filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
                    return static::normalizeDomain($domain, $protocol);
                }
            }
        }

        if($registeredDomain = $tenant->getRegisteredMainDomain()){
            return static::normalizeDomain($registeredDomain, $protocol);
        }

        return null;
    }
}
