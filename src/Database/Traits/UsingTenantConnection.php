<?php

namespace IbraheemGhazi\OmniTenancy\Database\Traits;

trait UsingTenantConnection
{
    public function getConnection()
    {
        return config('tenancy.connections.tenants');
    }
}
