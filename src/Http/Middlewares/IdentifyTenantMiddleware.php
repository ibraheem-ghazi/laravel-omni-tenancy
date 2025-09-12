<?php

namespace IbraheemGhazi\OmniTenancy\Http\Middlewares;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotActiveException;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotFoundException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class IdentifyTenantMiddleware
{
    public function __construct(
        protected TenantIdentifier $identifier,
        protected TenantContext    $context
    )
    {
    }

    /**
     * @throws \Throwable
     */
    public function handle(Request $request, Closure $next)
    {
        // the routes are better to be registered in the bootstrap.
        // but in boot() settings some configurations does not work
        // so we need to refresh current tenant to force the configuration
        // being correctly set.
        // You might think why not just load routes here instead of refresh.
        // Well routes supposed to be loaded before middleware.
        // This middleware is global which runs before routes, well yeah,
        // but registering routes here might work, but route() function
        // will not recognize the route and will through route not exists
        // despite its exists.

        $this->context->refreshTenant();

        return $next($request);
    }
}
