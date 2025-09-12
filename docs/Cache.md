# Tenant-Aware Caching

## Overview

This package implements a robust tenant-aware caching system that ensures all cache operations are isolated per tenant. This includes not only application data but also route caching, which is critical for SaaS platforms with tenant-specific routing requirements.

---

## How Tenant Context Updates Cache

The package dynamically adjusts the cache key prefix based on the current tenant context. When a tenant is resolved (for example, by domain or subdomain), the cache system automatically switches to use a unique prefix for that tenant. This ensures that cache entries for one tenant are never accessible by another.

## Route Caching

The default route cache system in Laravel compiles the routes and writes the content to `routes-v7.php` in the `bootstrap/cache` folder. This package extends that feature to compile routes per tenant and write them to a tenant-specific path. When a tenant context is activated, the system automatically loads the routes—if cached—from the compiled and cached routes version.

We have replaced the `route:cache` command with `tenant:route:cache` and the `route:clear` command with `tenant:route:clear`. The original, non-`tenant:`-prefixed commands are disabled when this package is active.

### Caching or Clearing Routes for a Specific Tenant

There are two possible ways to cache or clear routes for a specific tenant:

```bash
php artisan tenant:run 1 tenant:route:cache
php artisan tenant:run 1 tenant:route:clear
# or
php artisan tenant:route:cache 1
php artisan tenant:route:clear 1
```

## Caching or Clearing Routes for All Tenants
To cache or clear routes for all tenants at once, utilize the tenant:run command:
```bash
# You can add --yes to bypass the confirmation
php artisan tenant:run all tenant:route:cache 
php artisan tenant:run all tenant:route:clear 
```

## Caching or Clearing Routes for Multiple Tenants
To cache or clear routes for multiple tenants at once, use the following command:
```bash
php artisan tenant:run 1,2,3 tenant:route:cache 
php artisan tenant:run 1,2,3 tenant:route:clear 
```