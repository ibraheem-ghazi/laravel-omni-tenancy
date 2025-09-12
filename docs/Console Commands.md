# Console Commands

This package provides a comprehensive set of Artisan console commands to manage tenants, databases, files, routes, and maintenance modes in a multi-tenant Laravel application. The commands are designed to enforce tenant context, ensuring operations are isolated and safe. Some commands are restricted and must be executed within a tenant context, while others are globally accessible.

## Command Execution Context

Most tenancy-related commands **must be run within a tenant context**. This is enforced by the package’s [TenancyConsoleBootstrapper](../src/Core/Bootstrappers/TenancyConsoleBootstrapper.php), which prevents accidental execution of sensitive commands outside the intended tenant scope. If you attempt to run a restricted command outside tenant context, you will see an error and instructions to use the `tenant:run` wrapper.

**Example:**
```shell
php artisan tenant:run TENANT_ID COMMAND [args] [+option=value ...]
```
- `TENANT_ID` can be a specific tenant ID, a comma-separated list, or `all` for all tenants.
- Use `+option=value` instead of `--option=value` for command options.

**Bypassing Restrictions:**  
For advanced use cases, you can bypass tenancy restrictions by setting the environment variable:
```shell
TENANCY_BYPASS=1 php artisan COMMAND ...
```
> **Warning:** Use this with caution. Bypassing tenancy context can lead to data leaks or corruption.

## Command List

Below is a summary of all console commands provided by the package, with a description and usage for each.

---

### `tenant:run`

**Description:**  
Executes any allowed Artisan command in the context of one or more tenants.

**Usage:**  
```shell
php artisan tenant:run {tenant} {cmd} [cmdArgs...] [+option=value ...]
```
- `{tenant}`: Tenant ID, comma-separated IDs, or `all`
- `{cmd}`: The Artisan command to run
- `[cmdArgs...]`: Arguments for the command
- `+option=value`: Options for the command

**Options:**
- `--yes`: Skip confirmation when running for all tenants
- `--dry-run`: Preview without execution
- `--skip-errors`: Continue on errors in batch mode
- `--limit=`: Limit number of tenants in batch mode
- `--chunk=500`: Process tenants in chunks
- `--active-only`: Only process active tenants
- `--cmd-verbose`: Output command execution details

---

### `tenant:list`

**Description:**  
Lists all tenants with their details (ID, name, domain, database, route groups, status).

**Usage:**  
```shell
php artisan tenant:list [--active-only]
```
- `--active-only`: Only show active tenants

---

### `tenant:init`

**Description:**  
Initializes the tenancy system, migrates the central database, creates the Central and Manager tenants, and prepares route folders.

**Usage:**  
```shell
php artisan tenant:init
```

---

### `tenant:migrate`

**Description:**  
Runs database migrations for the selected tenant.

**Usage:**  
```shell
php artisan tenant:migrate [tenantId]
```
- Should be run via `tenant:run` or with a tenant context.

---

### `tenant:seed`

**Description:**  
Seeds the database for the selected tenant.

**Usage:**  
```shell
php artisan tenant:seed [tenantId] [--class=SeederClass]
```
- `--class`: Specify a seeder class to run

---

### `tenant:route:cache`

**Description:**  
Caches routes for the selected tenant for faster registration.

**Usage:**  
```shell
php artisan tenant:route:cache [tenantId]
```

---

### `tenant:route:clear`

**Description:**  
Clears the route cache for the selected tenant.

**Usage:**  
```shell
php artisan tenant:route:clear [tenantId]
```

---

### `tenant:backup:database`

**Description:**  
Backs up the database for the current tenant.

**Usage:**  
```shell
php artisan tenant:backup:database
```
- Must be run within tenant context.

---

### `tenant:backup:files`

**Description:**  
Backs up application files for the current tenant.

**Usage:**  
```shell
php artisan tenant:backup:files
```
- Must be run within tenant context.

---

### `tenant:backup:full`

**Description:**  
Performs a full backup (database and files) for the current tenant.

**Usage:**  
```shell
php artisan tenant:backup:full
```
- Must be run within tenant context.

---

### `tenant:restore:database`

**Description:**  
Restores the tenant's database from a backup.

**Usage:**  
```shell
php artisan tenant:restore:database {backupFile}
```
- `{backupFile}`: Path to the backup SQL file

---

### `tenant:restore:files`

**Description:**  
Restores tenant files from a backup ZIP file.

**Usage:**  
```shell
php artisan tenant:restore:files {backupZip}
```
- `{backupZip}`: Path to the backup ZIP file

---

### `tenant:restore:full`

**Description:**  
Restores both database and files for the tenant from backups.

**Usage:**  
```shell
php artisan tenant:restore:full {backupDir}
```
- `{backupDir}`: Directory containing backup files

---

### `tenant:tinker`

**Description:**  
Starts a Tinker shell in the context of the selected tenant.

**Usage:**  
```shell
php artisan tenant:tinker [tenantId] [--execute=code] [--include=file ...]
```
- `--execute`: Execute the given code using Tinker
- `--include`: Include files before starting Tinker

---

### `tenant:maintenance`

**Description:**  
Enables or disables maintenance mode for the current tenant, allowing you to show a custom maintenance page, redirect users, or require a secret for bypass.

**Usage:**  
```shell
php artisan tenant:maintenance {enabled} [options]
```
- `{enabled}`: `1` to enable maintenance mode, `0` to disable

**Options:**
- `--redirect=`: Path or URL to redirect users during maintenance (e.g., `/maintenance`)
- `--render=`: Blade view to prerender as the maintenance page
- `--retry=`: Number of seconds after which clients should retry (sets `Retry-After` header)
- `--refresh=`: Number of seconds after which the browser should auto-refresh the page
- `--secret=`: Secret phrase that allows bypassing maintenance mode (can be used as a query parameter or path)


**Notes:**
- When enabled, all requests to the tenant will be intercepted and handled according to the provided options.
- If a `--secret` is set, users with the correct secret can bypass maintenance mode.
- The `--render` option allows you to specify a custom Blade view for the maintenance page, which can receive variables like `retryAfter`.
- The `--redirect` option takes precedence over `--render` if both are provided.
- Maintenance mode is enforced by the [`TenancyMaintenanceModeMiddleware`](../src/Http/Middlewares/TenancyMaintenanceModeMiddleware.php).

---

## Additional Notes

- All commands that affect tenant data (migrations, seeds, backups, restores, etc.) should be run in the correct tenant context, either directly or via `tenant:run`.
- For more advanced automation, you can use the `tenant:run` command to execute any Artisan command for multiple tenants in batch mode.
---