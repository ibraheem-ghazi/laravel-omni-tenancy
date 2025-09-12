<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\Bootstrappers\TenancyRoutesBootstrapper;
use IbraheemGhazi\OmniTenancy\Events\TenantContextActivatedEvent;
use IbraheemGhazi\OmniTenancy\Events\TenantContextResettedEvent;
use IbraheemGhazi\OmniTenancy\Events\TenantContextRouteBootstrappedEvent;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotFoundException;
use IbraheemGhazi\OmniTenancy\Http\Middlewares\IdentifyTenantMiddleware;
use IbraheemGhazi\OmniTenancy\Core\TenancyRouter;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Str;
use Throwable;

class MultiTenantContext implements TenantContext
{
    use Macroable;

    protected static ?TenantObject $currentTenant = null;

    protected static array $config = [];

    private static array $cachedConfig = [
        'cache.prefix' => '',
    ];
    private static bool $cachedConfigUpdated = false;

    /**
     * @throws Throwable
     */
    public function setTenant(string|int|TenantObject $id): void
    {
        if($id instanceof TenantObject) {
            $tenant = $id;
        } else {
            $tenant = resolve(TenantRegistry::class)->getTenant($id);
            throw_unless($tenant, TenantNotFoundException::make($id));
        }
        static::$currentTenant = $tenant;

        $tenantConnection = config('tenancy.connections.tenants', config('database.default'));
        $this->setConfigIf("database.default", $tenantConnection); // this change the default connection so it all commands work on its db
        $this->setConfigIf("database.connections.$tenantConnection.database", $tenant->getDatabaseName());
        $this->setConfigIf("database.connections.$tenantConnection.username", $tenant->getDatabaseUser());
        $this->setConfigIf("database.connections.$tenantConnection.password", $tenant->getDatabasePassword());

        $this->setConfig('app.name', $tenant->getName());
        $this->setConfig('app.url', $tenant->getMainDomain());
        url()->useOrigin($tenant->getMainDomain());

        $this->initCachedConfig();

        $this->setConfig('cache.prefix', (static::$cachedConfig['cache.prefix']."[TENANTS]" ?: 'TENANTS') . "[{$tenant->getId()}]");

        foreach(config('tenancy.suffixed_paths.config_keys') as $key=>$value)
        { 
            $pathConfigKey = is_numeric($key) ? $value : $key;
            $suffix = is_numeric($key) ? config('tenancy.suffixed_paths.suffix_to_add', 'tenants/%id') : $value;

            $suffix = Str::of($suffix)
                ->replace('%id', $tenant->getId())
                ->replace('%hash', $tenant->getHash())
                ->replace('%name', $tenant->getName())
                ->replace('%slugged-name', Str::slug($tenant->getName()))
                ->toString();

            $this->setConfig($pathConfigKey, rtrim(static::$cachedConfig[$pathConfigKey], '/') . '/' . ltrim($suffix, '/'));
            if(!is_dir(config($pathConfigKey))) mkdir(config($pathConfigKey), 0755, true);
        }

        $sessCookiePrefix = config('tenancy.session.cookie_prefix') ?: 'sesst_';
        $sessCookieSuffix = config('tenancy.session.cookie_suffix') ?: '';
        $this->setConfig('session.cookie', $sessCookiePrefix . $tenant->getHash() . $sessCookieSuffix);

        DB::purge($tenantConnection);
        DB::reconnect($tenantConnection);

        TenantContextActivatedEvent::dispatch($tenant);

        $_ENV['APP_ROUTES_CACHE'] = TenancyRouter::getCachedRoutesPathByTenant($tenant);
        
        resolve(TenancyRoutesBootstrapper::class)->bootstrap(true);

        TenantContextRouteBootstrappedEvent::dispatch($tenant);

    }

    private function initCachedConfig()
    {
        if(static::$cachedConfigUpdated){
            return;
        }
        static::$cachedConfigUpdated = true;

        if(!filled(static::$cachedConfig['cache.prefix']) && filled(config('cache.prefix'))){
            static::$cachedConfig['cache.prefix'] = config('cache.prefix');
        }

        foreach(config('tenancy.suffixed_paths.config_keys') as $key=>$value)
        { 
            $pathConfigKey = is_numeric($key) ? $value : $key;
            if((!isset(static::$cachedConfig[$pathConfigKey]) || !filled(static::$cachedConfig[$pathConfigKey])) && filled(config($pathConfigKey))){
                static::$cachedConfig[$pathConfigKey] = config($pathConfigKey);
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function usingTenant(TenantObject|int|string|null $tenant, callable $callback): mixed
    {
        $currentTenant = $this->getCurrentTenant();
        try{
            if(filled($tenant)){
                $this->setTenant($tenant);
            }else{
                $this->reset();
            }
            $output = call_user_func($callback, $this->getCurrentTenant());
        } finally {
            if($currentTenant)
                $this->setTenant($currentTenant);
            else
                $this->reset();
        }
        return $output;
    }

    /**
     * @see IdentifyTenantMiddleware
     * @throws Throwable
     */
    public function refreshTenant(): void
    {
        if(!$this->getCurrentTenant()){
            return;
        }
        $this->setTenant($this->getCurrentTenant());
    }


    public function reset(): void
    {
        static::$currentTenant = null;
        $this->resetConfig();

        $tenantConnection = config('tenancy.connections.tenants', config('database.default'));
        DB::disconnect($tenantConnection);

        $centralConnection = config('tenancy.connections.tenants', config('database.default'));
        DB::purge($centralConnection);
        DB::reconnect($centralConnection);

        TenancyRouter::reset();

        TenantContextResettedEvent::dispatch();
    }

    public function getCurrentTenant(): ?TenantObject
    {
        return static::$currentTenant;
    }


    public function hasTenant(): bool
    {
        return boolval(static::$currentTenant);
    }

    private function setConfigIf($key, $value): void
    {
        if(!filled($value)){
            return;
        }
        $this->setConfig($key, $value);
    }
    private function setConfig($key, $value): void
    {
       if(!isset(self::$config[$key]))
            self::$config[$key] = config($key);
        Config::set($key, $value);
    }

    private function resetConfig(): void
    {
        foreach(self::$config as $key=>$value)
        {
            Config::set($key, $value);
        }
        self::$config = [];
    }
}
