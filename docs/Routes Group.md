Routes Group
============

## Introduction
In Laravel, routes are straight forward, registered and loaded, we used to register routes using Route facade methods, like get(...), post(...), group(...), ...etc, -and we won't change that of course-, Laravel by itself does not need a way to dynamically handle the routes, and by that, on the fly add, and delete routes, therefore, in this package, we extended the Router class and RouterCollection, allowed it to delete specific routes from its lookup table, like permanently remove the route, not just prevent accessing it. and to dynamic adding new routes, nothing new, just use the Route facade methods as we used.

## Why Routes Group ?
When having a multi-tenants system, many applications will have different plans, where the higher plan opens more feature to user, and could those features could require new routes, Well, usually these routes always registered, but being prevented to access unless the tenant got privilegs to this feature, this sometimes leads to more confusion while developing especially if the application grow more and more and had more than +100 routes, not to mention it give more precise output at commands like "route:list", running it under the command "tenant:run", will list only and only routes belongs to this tenant registered routes group.

## How to register your own custom route group?

You can register your routes group by calling 
```php
\IbraheemGhazi\OmniTenancy\Core\TenancyRouter::group('GROUP_NAME', $callback);
```

> NOTE: at the path `routes/tenancy/ROUTE_GROUP/(web|api).php` you dont need to manually call `TenancyRouter::group` as it automatically called you just need to create the folder with `ROUTE_GROUP` name (ex: routes/tenancy/pro-plan/web.php) and this will register routes under this file to routes group named `pro-plan`

## Useful Methods under TenancyRouter

----------------------------------------------------------
|Method| Description |
|TenancyRouter::group()| - |
|TenancyRouter::removeRoutesGroupInList()| - |
|TenancyRouter::removeRoutesGroupNotInList()| - |
|TenancyRouter::loadGroupRoutes()| - |
----------------------------------------------------------

> IMPORTANT NOTE: The package uses deep concept of handling the routes and grouping, which require flushing, and re-loading of all routes based on the tenant context changing, which require reloading routes files that are inside "routes" folder for example, Therefore, it's important to mention that when requiring or including routes files inside each other, **DO NOT EVER USE "require_once" or "include_once"**, as this will force file to be loaded only once, leading -while switching- tenants for missing routes, therefore, just use "include" or "require" without the suffix "_once" to ensure everything is working as expected.


