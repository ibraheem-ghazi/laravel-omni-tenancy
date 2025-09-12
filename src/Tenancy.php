<?php

namespace IbraheemGhazi\OmniTenancy;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantCreator;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\Databases\AbstractTenantDatabaseManager;
use IbraheemGhazi\OmniTenancy\Core\Databases\MySQLTenantDatabaseManager;
use RuntimeException;

class Tenancy
{

    /**
     * global flag that control the Tenancy, when false, the main provider of the package will be disabled, 
     * leading to disable all functionalities of the tenancy and multi-tenants.
     */
    public static function isEnabled(): bool
    {
        return boolval(config('tenancy.enabled', false));
    }

    public static function registry(): TenantRegistry
    {
        return app(TenantRegistry::class);
    }
    public static function newCreator(): TenantCreator
    {
        return static::registry()->newCreator();
    }

    public static function context(): TenantContext
    {
        return app(TenantContext::class);
    }

    public static function databaseManager(?TenantObject $tenantObject = null): AbstractTenantDatabaseManager
    {
        if(!$tenantObject){
            $tenantObject = self::context()->getCurrentTenant();
        }

        /**
         * @var AbstractTenantDatabaseManager|string $managerClass
         */
        $managerClass = config('tenancy.database_manager', MySQLTenantDatabaseManager::class);

        if(!in_array(AbstractTenantDatabaseManager::class, class_parents($managerClass))){
            throw new RuntimeException("$managerClass must extends from " . AbstractTenantDatabaseManager::class);
        }

        return $managerClass::make($tenantObject);
    }
}
