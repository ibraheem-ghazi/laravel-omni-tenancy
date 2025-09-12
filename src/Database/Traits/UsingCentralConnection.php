<?php

namespace IbraheemGhazi\OmniTenancy\Database\Traits;

trait UsingCentralConnection
{
    public function getConnection()
    {
        return config('tenancy.connections.central');
    }
}
