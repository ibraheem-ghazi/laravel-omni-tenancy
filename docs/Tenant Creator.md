Tenant Creator
==============


## Introduction

Any multi tenants application, surely have its own registeration page, which usually end up creating a new tenant, so usually developer end up creating a strategy and the flow of creating new tenant, and sometimes they need a way to register the tenant information somewhere or create the tenant as inactive, in case there is a middle action required by the user like for example email activation, or mobile confirmation.
and Usually this became a bit complicated over time.
This tenancy package offer an easy way to create a new tenant with quick an easy way for that, moreover we offer a new concept called "tenant requests", this mean you can store the tenant information from registration form as "tenant request" and later when the user activate email or confirm mobile, from the same creator class, you can pass that "tenant request" and convert it to an active tenant with single line.

> NOTE: Of course usually you would need to do additional operations like seeding custom seeder or create more users or adjust some settings of it as you wish.

## How to use

You can access the creatore class via the `IbraheemGhazi\OmniTenancy\Tenancy` global class:
```php
    $creator = IbraheemGhazi\OmniTenancy\Tenancy::newCreator();
```

creating a "tenant" or a "tenant request" is a simple process:
```php
    $pending = Tenancy::newCreator()
            ->withDatabase()
           // ->withCustomDatabase('database_name', 'username', 'password')
            ->withName('Tenant Name')
            ->withRoutesGroups(['routes', 'group', 'list', 'here'])
            ->withDomain('tenant-domain.com')
            ->withSubDomain('subdomain')
            //->setActive(true|false)
            ->withOwnerInfo([
                'name' => 'Tenant Owner Name',
                'email' => 'email@provider.ext',
                // any other info related to owner info you want to add
            ])
            ->withOptions([
                //'opt' => 'active',
                // any option you want to add for the tenant
            ]);
            

    // create the tenant request
    $tenantRequest = $pending->createRequest();

    // create the tenant
    $tenantDto     = $pending->createTenant();
```

To convert the "tenant request" into an active tenant simply call

```php
    $tenant = Tenancy::newCreator()->fromRequest($tenantRequest->getKey())
    //->keepRequestAfterCreateTenant()
   ->withRoutesCache()
    ->createTenant();
```

> NOTE: `fromRequest` can take either the TenantRequest model, or its id.


## Additional Useful Methods

----------------------------------------------------------
|Method| Description |
|Tenancy::newCreator()->hasTenantWithDomain(string $domain, bool $includeMapped = true)| check if there is a tenant in tenants table owning this domain |
|Tenancy::newCreator()->hasRequestWithDomain(string $domain, bool $includeMapped = true)| check if there is a request in tenant_requests owning this domain  |
|Tenancy::newCreator()->hasAnyWithDomain(string $domain, bool $includeMapped = true)| check if a tenant or a request owning this domain |
|Tenancy::newCreator()->deleteTenant(TenantObject|int|string|null $tenant) | delete the tenant with all its related database, and user, backups and assets |
----------------------------------------------------------

> NOTE: the parameter `$includeMapped` means when checking should consider the mapped domains via tenancy config of identifiers into account or act like they are not exists.


## Database Tenant Creator Class Additional Specific Methods

----------------------------------------------------------
|Method| Description |
|Tenancy::newCreator()::callSeeder(string|int|TenantObject $tenantId, string $seederClass)| seed specific class for specific tenant |
|Tenancy::newCreator()::seedDatabase(TenantObject|string|int $tenant)| seed the default configured seeder (same as tenant:seed) |
|Tenancy::newCreator()::migrateDatabase(TenantObject|string|int $tenant)| migrate the default tenancy migrations (same as tenant:migrate) |

----------------------------------------------------------



## Fail Behaviour

When creator failed it throw an exception of `TenantCreationFailedException` by default, this can be customized via 
```php
    IbraheemGhazi\OmniTenancy\Core\AbstractTenantCreator::$failBehaviour = function(?string $message){}
```

## Forbidded Values
If you read about the Tenant Identifiers section, you already know about excluding value, if you for example forbid a subdomain `admin` the creator will also be prevented from creating a tenant or a tenant request with that subdomain.