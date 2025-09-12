<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;

class TenancyRouteCollection extends RouteCollection
{
    public static function makeFromBase(RouteCollection $routeCollection): static
    {
        $instance = new static();
        $vars = array_keys(get_class_vars(get_class($routeCollection)));
        foreach ($vars as $var) {
            $instance->{$var} = $routeCollection->{$var};
        }
        return $instance;
    }

    /**
     * Remove a Route instance from the collection.
     *
     * @param Route $route
     * @return void
     */
    public function remove(Route $route): void
    {
        $this->removeFromCollections($route);
        $this->removeFromLookups($route);
    }

    /**
     * Remove the given route from the arrays of routes.
     *
     * @param Route $route
     * @return void
     */
    protected function removeFromCollections(Route $route): void
    {
        $methods = $route->methods();
        $domainAndUri = $route->getDomain().$route->uri();

        foreach ($methods as $method) {
            if (isset($this->routes[$method][$domainAndUri])) {
                unset($this->routes[$method][$domainAndUri]);

                if (empty($this->routes[$method])) {
                    unset($this->routes[$method]);
                }
            }
        }

        $allRoutesKey = implode('|', $methods).$domainAndUri;
        if (isset($this->allRoutes[$allRoutesKey])) {
            unset($this->allRoutes[$allRoutesKey]);
        }
    }

    /**
     * Remove the route from all look-up tables.
     *
     * @param Route $route
     * @return void
     */
    protected function removeFromLookups(Route $route): void
    {
        if ($name = $route->getName()) {
            unset($this->nameList[$name]);
        }

        $action = $route->getAction();
        if (isset($action['controller'])) {
            $controller = trim($action['controller'], '\\');
            if (isset($this->actionList[$controller])) {
                unset($this->actionList[$controller]);
            }
        }
    }
}
