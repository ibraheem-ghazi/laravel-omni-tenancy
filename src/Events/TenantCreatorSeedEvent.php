<?php

namespace IbraheemGhazi\OmniTenancy\Events;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use Illuminate\Foundation\Events\Dispatchable;

class TenantCreatorSeedEvent
{
    use Dispatchable;

    public ?TenantObject $tenant;

    public function __construct(?TenantObject $tenant)
    {
        $this->tenant = $tenant;
    }
}
