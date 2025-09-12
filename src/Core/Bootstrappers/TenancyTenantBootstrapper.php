<?php

namespace IbraheemGhazi\OmniTenancy\Core\Bootstrappers;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotActiveException;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotFoundException;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class TenancyTenantBootstrapper extends AbstractTenancyBootstrapper
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     * @throws ContainerExceptionInterface
     * @throws TenantNotFoundException
     * @throws TenantNotActiveException
     */
    public function handle(): void
    {
        if (app()->runningInConsole()) {
            return;
        }


        $tenant = resolve(TenantIdentifier::class)->identify(resolve(Request::class));

        if (!$tenant instanceof TenantObject) {
            throw TenantNotFoundException::make(session()->get('tenant_identifier'));
        }

        if (!$tenant->isActive()) {
            throw TenantNotActiveException::make($tenant->getId() . ' - ' . $tenant->getName());
        }

        Tenancy::context()->setTenant($tenant);
    }
}
