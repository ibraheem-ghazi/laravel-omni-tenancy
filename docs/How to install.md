How to Install ?
==============

```
composer require ibraheem-ghazi/omni-tenancy
```

Once that finished, go to config/database.php file and duplicate the connection named "mysql", to name "mysql_tenant", or as it configured at tenancy.php.

create folder `database/migrations/shared` and move the migrations that should run for all tenants to this folder (we talk about tenants databases not central database), such as: `cache`, `settings` (if u have it for example)

and under `database/migrations` the migrations the should run for both main database and the tenants databases, such as (jobs, and sessions)

> NOTE: usually jobs are not needed in the main database, and the sessions only needed in the main database when we have tenants that run without its own database.

then:
```
php artisan migrate 
php artisan tenant:init 
```

follow the printed instruction after `tenant:init` command.

for the routes group create the nested files `routes/tenancy/ROUTE_GROUP/web.php` and `routes/tenancy/ROUTE_GROUP/api.php`

if your installed laravel version does not support auto discover packages then:

1- add this provider to config:
```
IbraheemGhazi\OmniTenancy\TenancyServiceProvider::class,
```

2- then add alias:
```
'Tenancy' => IbraheemGhazi\OmniTenancy\Tenancy::class,