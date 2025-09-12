<?php

namespace IbraheemGhazi\OmniTenancy\Core\Bootstrappers;

use IbraheemGhazi\OmniTenancy\Core\SingleDatabaseTenancy;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class TenancySingleDatabaseBootstrapper extends AbstractTenancyBootstrapper
{
    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (filled(SingleDatabaseTenancy::$columnName))
            foreach (SingleDatabaseTenancy::getModels() as $model) {
                $model::addGlobalScope('tenancy', function (Builder $query) {
                    $query->where(SingleDatabaseTenancy::$columnName, Tenancy::context()->getCurrentTenant()?->getId());
                });
            }

    }
}
