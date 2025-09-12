# Maintenance Mode in Laravel Omni Tenancy

The **Laravel Omni Tenancy** package extends Laravel's native maintenance mode to support **per-tenant maintenance**, in addition to the standard application-wide maintenance.

---

## Native Laravel Maintenance Mode

- **Commands:**  
  - `php artisan down`  
  - `php artisan up`
- **Scope:**  
  - These commands affect the **entire application**. When enabled, all users (across all tenants) see the maintenance page or are redirected, regardless of which tenant they access.
- **Use Case:**  
  - Useful for global updates, deployments, or critical maintenance that impacts every tenant.

---

## Tenant-Specific Maintenance Mode

- **Command:**  
  - `php artisan tenant:maintenance {enabled} [options]`
    - `{enabled}`: `1` to enable, `0` to disable maintenance mode for the current tenant.
    - `[options]`: Additional options like `--redirect`, `--render`, `--secret`, etc.
- **Scope:**  
  - This command **only affects the selected tenant**. Other tenants remain fully accessible.
- **How it works:**  
  - When enabled, all requests for that tenant are intercepted by the [`TenancyMaintenanceModeMiddleware`](../src/Http/Middlewares/TenancyMaintenanceModeMiddleware.php).
  - You can customize the maintenance response per tenant:  
    - Show a custom page (`--render`)
    - Redirect to a specific path (`--redirect`)
    - Require a secret to bypass maintenance (`--secret`)
    - Set HTTP status, retry, and refresh headers
- **Use Case:**  
  - Ideal for tenant-specific upgrades, migrations, or temporary lockouts without affecting other tenants.

---

## Summary Table

| Command                        | Scope                | Description                                 |
|--------------------------------|----------------------|---------------------------------------------|
| `php artisan down` / `up`      | Whole Application    | Global maintenance for all tenants          |
| `php artisan tenant:maintenance` | Single Tenant        | Maintenance mode for a specific tenant only |

---

## Example Usage

Enable maintenance mode for tenant with ID 5, showing a custom view:

```shell
php artisan tenant:run 5 tenant:maintenance 1 --render=maintenance.custom
```

Disable maintenance mode for tenant 5:

```shell
php artisan tenant:run 5 tenant:maintenance 0
```

Enable maintenance mode for tenant 7, with a secret bypass:

```shell
php artisan tenant:run 7 tenant:maintenance 1 --secret=my-secret
```

---

## Important Notes

- **Do not use `down`/`up` for tenant-specific maintenance.**  
  Use `tenant:maintenance` for per-tenant control.
- **Middleware Enforcement:**  
  The package ensures that tenant maintenance mode is enforced at the HTTP layer, so only the targeted tenant is affected.
- **Automation:**  
  You can batch-enable or disable maintenance for multiple tenants using `tenant:run`.

---

For more details, see the [Console Commands documentation](./Console%20Commands.md#tenantmaintenance).