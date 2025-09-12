<?php

namespace IbraheemGhazi\OmniTenancy\Events;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use Illuminate\Foundation\Events\Dispatchable;

class TenantDeletedEvent
{
    use Dispatchable;

    public ?TenantObject $tenant;

    public function __construct(?TenantObject $tenant)
    {
        $this->tenant = $tenant;
    }
}
