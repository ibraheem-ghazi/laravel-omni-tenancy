Laravel Omni Tenancy (WIP)
==========
This is a Laravel package that allows your application to act as multi tenancy application, where everything is considered a tenant.

> IMPORTANT: THIS PACKAGE IS STILL WORK IN PROGRESS AND NOT YET READY FOR PRODUCTION.

## Compatibility
This package was built for versions >= 11.0 but also should be compatible with versions >= 8

## Features
* Quick and easy setup steps
* Isolated tenants routes, using tenancy routes grouping feature.
* Support creating tenants and tenants requests (pending creating tenants details)
* support database and files backup and restore per tenant.
* comprehensive console commands and commands protection that runs per tenant.
* Seeders and Migrations shared, and per route group.
* Easy extendable via Laravel Container bind method.
* Support single database tenants concept (simple wrapper concept, no backups, or other commands are extended for this feature).
* Predefined and extendable multi ways tenant identifications (domain, subdomain, header)
* Easy tenant creator class, with easier chained methods.
* Multiple predefined events.
////* Tenant bootstrapper support.
* Ready easy to use Tenant Creator class, with ability to control what to generate for it (domain?, database?, additional options, and more).
* At creator you can use withDatabase() for automated credentials generation or withCustomDatabase(..,..,..) to customize as you wish.
* At creator you can customize fail behaviour, currently it throw exception by default (AbstractTenantCreator::$failBehaviour)
* Per tenant routes cache instead of global routes cache (custom paths for saving/loading routes cache).
* Tenant restricted and based configurable servable files path.
* Main rule: Everything is a tenant.

## Concept
The main rule here is that everything is a Tenant, no matter wether it run as sub-domain or main domain, we have here two connections, 
the "Central" connection that handle main database storage where all tenants and domains informations are stored, and "Tenant" connection,
that where the tenant itself connects to.

## Important Notes To Be Considered
- The tenancy package copy the "app.url" config into "app.base_url" as the "app.url" will be dynamic and change per tenant, 
but the "app.base_url" will remain as it first "app.url" and tenancy will read from "app.base_url" when reading for sub-domains to make it 
as a full host.
- The package uses deep concept of handling the routes and grouping it via "tenancy routes group" concept, which require flushing, and re-loading of all routes based on the tenant context changing, which require reloading routes files that are inside "routes" folder for example, Therefor, it's important to mention that when requiring or including routes files inside each other, DO NOT EVER USE "require_once" or "include_once", as this will force file to be loaded only once, leading -while switching- tenants for missing routes, so TLDR, just use "include" or "require" without the suffix "_once" to ensure everything is working as expected.
- There are few commands that can be run separately or using "tenant:run TENANT_ID COMMAND ...", or "COMMAND TENANT_ID", for example, 
  "tenant:migrate 1" or "tenant:run 1 tenant:migrate", and the main reason behind this, is some commands need to run for multiple tenants at once and doing that manually is a pain, so we can just run "tenant:run all tenant:migrate --yes" and this will run migration for all tenants with single line command.
- If you set the session driver to "database", its required to migrate the "sessions" table into the central database, because tenants that does not have its own database will use central database.

## Installation

Since this package is not yet on Packagist, you must install it by adding the repository directly to your project's `composer.json` file.

### A. Add Repository Configuration

Open your project's `composer.json` file and add the following entry to the `repositories` section:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "[https://github.com/ibraheem-ghazi/laravel-omni-tenancy](https://github.com/ibraheem-ghazi/laravel-omni-tenancy)"
    }
],

### B. Require the package
After adding the repository, require the package using the following command (or manually add it to the require section):
```
composer require ibraheem-ghazi/laravel-omni-tenancy:dev-main
```
### C. Publish Configuration
```
php artisan vendor:publish --provider="IbraheemGhazi\OmniTenancy\OmniTenancyServiceProvider"
```

Once that finished, go to config/database.php file and duplicate the connection named "mysql", to name "mysql_tenancy", or as it configured at tenancy.php.

then:
```
TENANCY_BYPASS=1 php artisan migrate
php artisan tenant:init 
```

> NOTE: for `routes/web.php` remove routes and adjust `routes/tenancy/central/web.php` and `routes/tenancy/manager/web.php` for the main `/` route, and then it should work.

> NOTE: `routes/web.php` has priority over `routes/tenancy/**/*.php` so if you saw something override the tenancy routes its propably from this.

if your installed laravel version does not support auto discover packages then:

1- add this provider to config:
```
IbraheemGhazi\OmniTenancy\TenancyServiceProvider::class,
```

2- then add alias:
```
'Tenancy' => IbraheemGhazi\OmniTenancy\Tenancy::class,
```





