# Helpers

The **Laravel Omni Tenancy** package provides a suite of global helper functions to streamline multi-tenancy operations in your Laravel application. These helpers make your code more expressive, reduce boilerplate, and ensure tenant context is handled consistently throughout your application.

---

## Available Helpers

### `tenancy()`

Returns the main Tenancy manager instance, which provides access to tenant context and registry.

```php
$tenancy = tenancy();
$currentTenant = tenancy()->context()->getCurrentTenant();
```

---

### `tenant_asset($path, $secure = null, $tenant = null)`

Generates a URL for an asset, scoped to the current tenant context or a specific tenant.

```php
$logoUrl = tenant_asset('images/logo.png');
```

- **$path**: Asset path.
- **$secure**: (optional) Force HTTPS.
- **$tenant**: (optional) Tenant object, ID, or key.

This helper will generate a tenant-specific asset path if a tenant is resolved, otherwise it falls back to a global asset path.

---

### `tenant_url($path = null, $parameters = [], $secure = null, $tenant = null)`

Generates a URL based on the current tenant context or a given tenant.

```php
$url = tenant_url('profile/settings');
```

- **$path**: Path or full URL.
- **$parameters**: URL parameters.
- **$secure**: (optional) Force HTTPS.
- **$tenant**: (optional) Tenant object, ID, or key.

This helper rewrites the host in the URL to match the tenant's main domain if a tenant is resolved.

---

### `tenant_route($name, $parameters = [], $absolute = true, $tenant = null)`

Generates a URL for a named route, scoped to the current tenant context or a specific tenant.

```php
$url = tenant_route('dashboard');
```

- **$name**: The route name.
- **$parameters**: Route parameters.
- **$absolute**: Whether to generate an absolute URL.
- **$tenant**: (optional) Tenant object, ID, or key.

This helper generates a route URL and rewrites its host to match the tenant's main domain if a tenant is resolved.

---

## Extending Helpers

If you need additional helper functions, you can define them in your own application or extend the package's `helpers.php` file located at:

```
src/helpers.php
```