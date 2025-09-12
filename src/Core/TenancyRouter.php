<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use IbraheemGhazi\OmniTenancy\Http\Middlewares\TenancyRoutesGuardMiddleware;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as RouteEntity;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;

class TenancyRouter
{
    // maybe the only issue i currently detect is this always loaded in RAM
    // and useful and needed when switch between tenants, when current tenant
    // has cached routes, and switching to tenant not caching routes.
    protected static ?RouteCollection $initialRoutes = null; 

    public static function group(string $group, callable $callback): RouteRegistrar
    {
        /**
         * the middleware is not working only as guard, but also as a key to identify this route
         * belongs to which tenancy route group, or if not at all.
         * @see getApplicationRoutesByTenancyGroups
         */
        return Route::middleware(TenancyRoutesGuardMiddleware::group($group))->group($callback);
    }

    protected static function modifySystemRouters(callable $callback): void
    {
        $router = app('router');
        $routes = $router->getRoutes();
        $newRoutes = TenancyRouteCollection::makeFromBase($routes);
        call_user_func($callback, $newRoutes);
        $router->setRoutes($newRoutes);
    }

    protected static function getApplicationRoutesByTenancyGroups(): array
    {
        return array_reduce(app('router')->getRoutes()->getRoutes(), function(array $acc, RouteEntity $route){
            foreach($route->gatherMiddleware() as $mw){
                if(is_string($mw) && str_starts_with($mw, TenancyRoutesGuardMiddleware::group(''))){
                    $groupName = Str::of($mw)->after(':')->toString();
                    if(!isset($acc[$groupName]) || !is_array($acc[$groupName])){
                        $acc[$groupName] = [];
                    }
                    $acc[$groupName][] = $route;
                    break;
                }
            }
            return $acc;
        }, []);
    }

    public static function removeRoutesGroupInList(array $groups): void
    {
        $routes = static::getApplicationRoutesByTenancyGroups();
        foreach(array_keys($routes) as $registeredGroupName){
            if(!in_array($registeredGroupName, $groups)) {
                unset($routes[$registeredGroupName]);
            }
        }
        static::modifySystemRouters(function(TenancyRouteCollection $tenancyRouteCollection) use ($routes) {
            foreach ($routes as $routeCollection) {
                foreach ($routeCollection as $route) {
                    $tenancyRouteCollection->remove($route);
                }
            }
        });
    }


    public static function removeRoutesGroupNotInList(array $groups): void
    {
        $routes = static::getApplicationRoutesByTenancyGroups();
        foreach(array_keys($routes) as $registeredGroupName){
            if(in_array($registeredGroupName, $groups)) {
                unset($routes[$registeredGroupName]);
            }
        }
        static::modifySystemRouters(function(TenancyRouteCollection $tenancyRouteCollection) use ($routes) {
            foreach ($routes as $routeCollection) {
                foreach ($routeCollection as $route) {
                    $tenancyRouteCollection->remove($route);
                }
            }
        });
    }

    public static function loadGroupRoutes(string $group)
    {
        static::group($group, function () use($group) {
            if (file_exists(base_path("routes/tenancy/$group/web.php")))
                Route::middleware('web')->group(base_path("routes/tenancy/$group/web.php"));
            if (file_exists(base_path("routes/tenancy/$group/api.php")))
                Route::middleware('api')->prefix('api')->group(base_path("routes/tenancy/$group/api.php"));
        });
    }

    public static function getCachedRoutesPathByTenant(TenantObject $tenantDto): string
    {
        return base_path('bootstrap/cache/tenancy-cache/' . $tenantDto->getId() . '/routes.php');
    }

    public static function hasTenantCompiledRoutes(TenantObject $tenantDto): bool
    {
        $compiledPath= static::getCachedRoutesPathByTenant($tenantDto);
        return file_exists($compiledPath) && is_file($compiledPath) && is_readable($compiledPath);
    }
    
    protected static function loadCompiledRoutesByTenant(TenantObject $tenantDto): bool
    {
        if(!static::hasTenantCompiledRoutes($tenantDto))
        {
            return false;
        }

        require static::getCachedRoutesPathByTenant($tenantDto);

        return true;
    }

    public static function initIfNeeded()
    {
        if(isset(static::$initialRoutes)){
            return;
        }
        if(!app('router')->getRoutes() instanceof RouteCollection){
            return;
        }
        static::$initialRoutes = clone app('router')->getRoutes();

    }

    public static function refreshTenancyRoutes(TenantObject $tenantDto)
    {
        if(app()->isBooted()){
            static::refreshTenancyRoutesOnlyIfAppBooted($tenantDto);
        }else{
            app()->booted(fn()=>static::refreshTenancyRoutesOnlyIfAppBooted($tenantDto));
        }
    }

    public static function refreshTenancyRoutesOnlyIfAppBooted(TenantObject $tenantDto)
    {
        /**
         * Why "OnlyIfAppBooted" ?
         *  As per my understand when the TenancyRouter is called first time the app is not booted, so not all routes
         *  are loaded, so when switching between tenants, especially from cached routes tenant, to non cached routes
         *  tenant, it lead to some missing routes, the solutation i came up with is to refresh only when the app is
         *  booted and all routes at this stage should normally be registered, then we call "initIfNeeeded", which
         *  will take fresh clone of the current App Router. saving our non-tenancy routes from being lost.
         *
         * NOTE: In my case, without app booted only debugbar routes are loaded, but
         *  (up, storage/{path}, sanctum/csrf-cookie, broadcasting/auth ) where lost.
         */

        static::initIfNeeded();

        if(static::loadCompiledRoutesByTenant($tenantDto)){
            return; // compiled routes seems loaded so no need to continue
        }

        assert(static::$initialRoutes instanceof RouteCollection, "Tenancy Route is not initialized!");

        if(app('router')->getRoutes() instanceof CompiledRouteCollection){
            static::reset();
        }else{
            static::removeRoutesGroupNotInList($tenantDto->getGroups());
        }
        
        foreach ($tenantDto->getGroups() as $group) {
            static::loadGroupRoutes($group);
        }

        tap(app('router')->getRoutes(), function ($routes) {
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        });
    }

    public static function reset()
    {
        if(app('router')->getRoutes() instanceof CompiledRouteCollection){
            app('router')->setRoutes(static::$initialRoutes);
        }
        static::removeRoutesGroupNotInList([]);
    }
}
