<?php

namespace IbraheemGhazi\OmniTenancy\Models\Traits;

trait HasTenantConnection
{
    public function getConnectionName()
    {
        return config('tenancy.connections.tenants');
    }
}
