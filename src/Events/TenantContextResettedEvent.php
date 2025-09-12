<?php

namespace IbraheemGhazi\OmniTenancy\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TenantContextResettedEvent
{
    use Dispatchable;

    public function __construct()
    {
    }
}
