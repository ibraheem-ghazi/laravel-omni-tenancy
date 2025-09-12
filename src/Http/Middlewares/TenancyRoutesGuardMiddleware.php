<?php

namespace IbraheemGhazi\OmniTenancy\Http\Middlewares;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class TenancyRoutesGuardMiddleware
{
    public function handle(Request $request, Closure $next, string $group = 'default')
    {
        $tenant = Tenancy::context()->getCurrentTenant();

        if(!$tenant?->hasGroup($group)){
            abort(404);
        }

        return $next($request);
    }

    public static function group(string $group = 'default'): string
    {
        return static::class . ':' . $group;
    }
}
