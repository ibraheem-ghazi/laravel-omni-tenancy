<?php

namespace IbraheemGhazi\OmniTenancy\Events;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use Illuminate\Foundation\Events\Dispatchable;

class TenantMaintenanceModeChangedEvent
{
    use Dispatchable;

    public TenantObject $tenant;
    public array $data;

    public function __construct(TenantObject $tenant, array $data)
    {
        $this->tenant = $tenant;
        $this->data = $data;
    }
}
