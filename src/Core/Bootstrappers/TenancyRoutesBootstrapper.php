<?php

namespace IbraheemGhazi\OmniTenancy\Core\Bootstrappers;

use IbraheemGhazi\OmniTenancy\Core\TenancyRouter;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Support\Facades\Route;
use Throwable;

class TenancyRoutesBootstrapper extends AbstractTenancyBootstrapper
{

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if($tenant = Tenancy::context()->getCurrentTenant())
        {
            TenancyRouter::refreshTenancyRoutes($tenant);
        }
    }
}
