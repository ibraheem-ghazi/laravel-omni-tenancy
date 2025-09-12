<?php

namespace IbraheemGhazi\OmniTenancy\Core\Bootstrappers;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotFoundException;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class TenancyMaintenanceModeBootstrapper extends AbstractTenancyBootstrapper
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     * @throws ContainerExceptionInterface
     * @throws TenantNotFoundException
     */
    public function handle(): void
    {

        $tenant = Tenancy::context()->getCurrentTenant();

        if (!$tenant instanceof TenantObject) {
            throw TenantNotFoundException::make(session()->get('tenant_identifier'));
        }

        $isMaintenanceModeActive = boolval($tenant->getOption('maintenance_mode'));

        if($isMaintenanceModeActive){
            app()->maintenanceMode()->activate([]);
        }
    }
}
