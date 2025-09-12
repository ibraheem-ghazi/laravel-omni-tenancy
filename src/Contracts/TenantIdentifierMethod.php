<?php

namespace IbraheemGhazi\OmniTenancy\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface TenantIdentifierMethod
{
    public function identify(Request $request): ?TenantObject;

    public function setConfig(null|array|Collection $config = null): static;
    public function getDomainTenantIdMapping(string $fullHost): mixed;
    public function isHostForbidden(string $fullHost): bool;

    public function getFullHostFromInput(string $input) : ?string;
}
