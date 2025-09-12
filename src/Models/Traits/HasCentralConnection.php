<?php

namespace IbraheemGhazi\OmniTenancy\Models\Traits;

trait HasCentralConnection
{
    public function getConnectionName()
    {
        return config('tenancy.connections.central');
    }
}
