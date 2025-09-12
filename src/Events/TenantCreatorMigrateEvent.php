<?php

namespace IbraheemGhazi\OmniTenancy\Events;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use Illuminate\Foundation\Events\Dispatchable;

class TenantCreatorMigrateEvent
{
    use Dispatchable;

    public ?TenantObject $tenant;

    public function __construct(?TenantObject $tenant)
    {
        $this->tenant = $tenant;
    }
}
