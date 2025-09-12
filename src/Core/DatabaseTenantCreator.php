<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\DTO\DatabaseTenantObject;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantCreationFailedException;
use IbraheemGhazi\OmniTenancy\Models\Tenant;
use IbraheemGhazi\OmniTenancy\Models\TenantRequest;
use IbraheemGhazi\OmniTenancy\Tenancy;
use IbraheemGhazi\OmniTenancy\Events\TenantCreatorMigrateEvent;
use IbraheemGhazi\OmniTenancy\Events\TenantCreatorSeedEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;


class DatabaseTenantCreator extends AbstractTenantCreator
{
    private ?TenantRequest $tenantRequest = null;

    public function createRequest()
    {
        $domain = data_get($this->attributes, 'domain');
        $hasDomainValue = filled($domain);

        if (!$hasDomainValue) {
            $this->abortAndFail("Request must have domain associated");
        }

        if ($this->isForbiddenHost($domain)) {
            $this->abortAndFail("\"$domain\" host can not be used");
        }

        if ($this->hasAnyWithDomain($domain)) {
            $this->abortAndFail("\"$domain\" host already taken");
        }

        $model = TenantRequest::create([
            'name' => data_get($this->attributes, 'name'),
            'domain' => data_get($this->attributes, 'domain'),
            'routes_group' => data_get($this->attributes, 'routes.group'),
            'db_name' => data_get($this->attributes, 'db.name'),
            'db_user' => data_get($this->attributes, 'db.user'),
            'db_pass' => data_get($this->attributes, 'db.pass'),
            'options' => data_get($this->attributes, 'options'),
            'owner_info' => data_get($this->attributes, 'owner_info'),
        ]);
        return $model;
    }

    public function fromRequest(int|string|TenantRequest $tenantRequest): static
    {
        if(!$tenantRequest instanceof TenantRequest){
            $tenantRequest = TenantRequest::findOrFail($tenantRequest);
        }

        $this->tenantRequest = $tenantRequest;

         $attributes = [
            'options' => data_get($tenantRequest, 'options'),
            'db' => [
                'name' => data_get($tenantRequest, 'db_name'),
                'user' => data_get($tenantRequest, 'db_user'),
                'pass' => data_get($tenantRequest, 'db_pass'),
            ],
            'name' => data_get($tenantRequest, 'name'),
            'routes' => [
                'group' => data_get($tenantRequest, 'routes_group')
            ],
            'domain' => data_get($tenantRequest, 'domain'),

            'owner_info' => data_get($tenantRequest, 'owner_info')
        ];
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    public function createTenant(): ?TenantObject
    {
        $domain = data_get($this->attributes, 'domain');
        $hasDomainValue = filled($domain);

        if ($hasDomainValue  && $this->isForbiddenHost($domain)) {
            $this->abortAndFail("\"$domain\" host can not be used");
        }

        // if it from request bypass checking domain existance at requests, cause for sure its already exists, 
        // we just check if there is a tenant or not with that domain.
        if ($hasDomainValue 
            && (
                (!$this->tenantRequest && $this->hasAnyWithDomain($domain))
                || ($this->tenantRequest && $this->hasTenantWithDomain($domain))
            )) {
            $this->abortAndFail("\"$domain\" host already taken");
        }

        $tenant = Tenant::query()->create(Arr::only($this->attributes, [
            'name',
            'active',
        ]));


        if ($hasDomainValue)
            $tenant->createMainDomain($domain);

        $tenantDto = DatabaseTenantObject::makeFromModel($tenant);

        foreach (data_get($this->attributes, 'options') as $key => $value) {
            $tenantDto->setOption($key, $value);
        }

        if (filled($dbName = data_get($this->attributes, 'db.name')))
            $tenantDto->setEncryptedOption('db.name', $dbName);

        if (filled($dbUser = data_get($this->attributes, 'db.user')))
            $tenantDto->setEncryptedOption('db.user', $dbUser);

        if (filled($dbPass = data_get($this->attributes, 'db.pass')))
            $tenantDto->setEncryptedOption('db.pass', $dbPass);

        foreach (data_get($this->attributes, 'routes.group') as $group) {
            $tenantDto->addGroup($group);
        }

        if (filled($ownerInfo = data_get($this->attributes, 'owner_info'))){}
            $tenantDto->setMultipleOwnerInfo($ownerInfo);

        if ($dbName) {
            Tenancy::databaseManager()->createDatabase($tenantDto->getDatabaseName());
        }

        if ($dbUser) {
            Tenancy::databaseManager()->createUser($dbUser, $dbPass, $dbName);
        }


        TenantCreatorMigrateEvent::dispatch($tenantDto);
        if(!config('tenancy.creator.migrate_using_event_only')){
            static::migrateDatabase($tenantDto);
        }

        TenantCreatorSeedEvent::dispatch($tenantDto);
        if(!config('tenancy.creator.seed_using_event_only')){
            static::seedDatabase($tenantDto);
        }

        if($this->tenantRequest && !$this->keep_request_after_create_tenant){
            $this->tenantRequest->delete();
        }

        if($this->with_routes_cache){
             Artisan::call('tenant:run', [
                'tenant' => $tenantDto->getId(),
                'cmd' => 'tenant:route:cache',
            ]);
        }

        return $tenantDto;
    }

    public function hasAnyWithDomain(string $domain, bool $includeMapped = true): bool
    {
        return $this->hasRequestWithDomain($domain, $includeMapped) || $this->hasTenantWithDomain($domain, $includeMapped);
    }

    public function hasTenantWithDomain(string $domain, bool $includeMapped = true): bool
    {
        if ($includeMapped && $this->hasMappedDomain($domain)) {
            return true;
        }
        return Tenancy::registry()->existsTenantByDomain($domain);
    }

    public function hasRequestWithDomain(string $domain, bool $includeMapped = true): bool
    {
        if ($includeMapped && $this->hasMappedDomain($domain)) {
            return true;
        }
        return TenantRequest::where('domain', $domain)->exists();
    }

    protected function hasMappedDomain(string $domain): bool
    {
        foreach (TenantIdentifier::resolveIdentificationMethods() as $instance) {
            if (filled($instance->getDomainTenantIdMapping($domain))) {
                return true;
            }
        }
        return false;
    }

    protected function isForbiddenHost(string $domain): bool
    {
        foreach (TenantIdentifier::resolveIdentificationMethods() as $instance) {
            if($instance->isHostForbidden($domain)){
                return true;
            }
        }
        return false;
    }

    /**
     * if you updated the routes group its good to consider to call the migration if needed
     * so, if route group added and has its own migration then it will migrate
     * @param TenantObject|string|int $tenant
     * @return mixed|void
     */
    public static function migrateDatabase(TenantObject|string|int $tenant)
    {
        if (!$tenant instanceof TenantObject) {
            $tenant = Tenancy::registry()->getTenant($tenant);
        }

        // if(!filled($tenant->getDatabaseName())){
        //     return;
        // }

        $paths = config('tenancy.database.migration_paths', []);
        assert(is_array($paths), "\"tenancy.database.migration_paths\" must be an array");
        //  assert(!empty($paths), "\"tenancy.database.migration_paths\" must not be empty");

        $pathParams = [];
        foreach ($paths as $path) {
            $pathParams[] = '+path=' . rtrim(database_path("migrations"), '/') . '/' . ltrim($path, '/');
        }
        foreach ($tenant->getGroups() as $group) {
            $configKey = "tenancy.database.routes_group_migrations.$group";
            $rgPaths = config($configKey, []);
            assert(is_array($rgPaths), "$configKey must be array");
            foreach ($rgPaths as $path) {
                $pathParams[] = '+path=' . rtrim(database_path("migrations"), '/') . '/' . ltrim($path, '/');
            }
        }

        Artisan::call('tenant:run', [
            'tenant' => $tenant->getId(),
            'cmd' => 'migrate',
            'cmdArgs' => [
                '+step',
                '+realpath',
                '+database=' . config('tenancy.connections.tenants', config('database.default')),
                ...$pathParams
            ],
        ]);

        $artisanOutput = Artisan::output();

//        echo "<pre><code>{$artisanOutput}</code></pre>";

        if (app()->runningInConsole()) {
            echo $artisanOutput;
        }
        return $artisanOutput;
    }

    /**
     * if you updated the routes group its good to consider to call the seeders if needed
     * so, if route group added and has its own seeder then it will migrate
     *
     * @param TenantObject|string|int $tenant
     * @return void|null
     */
    public static function seedDatabase(TenantObject|string|int $tenant)
    {
        if (!$tenant instanceof TenantObject) {
            $tenant = Tenancy::registry()->getTenant($tenant);
        }

        $seederClass = config('tenancy.database.seeder_class');
        if (!$seederClass) {
            return null;
        }

        assert(class_exists($seederClass), "\"tenancy.database.seeder_class\" class not exists");

        static::callSeeder($tenant->getId(), $seederClass);

        foreach ($tenant->getGroups() as $group) {
            $configKey = "tenancy.database.routes_group_seeders.$group";
            $rgSeederClass = config($configKey, []);
            if (filled($rgSeederClass)) {
                assert(class_exists($rgSeederClass), "$configKey must be a valid class");
                static::callSeeder($tenant->getId(), $rgSeederClass);
            }
        }

    }

    public static function callSeeder(string|int|TenantObject $tenantId, string $seederClass): string
    {
        if($tenantId instanceof TenantObject){
            $tenantId = $tenantId->getId();
        }
        Artisan::call('tenant:run', [
            'tenant' => $tenantId,
            'cmd' => 'db:seed',
            'cmdArgs' => [
                '+database=' . config('tenancy.connections.tenants', config('database.default')),
                '+class=' . $seederClass,
            ]
        ]);

        $artisanOutput = Artisan::output();

        if (app()->runningInConsole()) {
            echo $artisanOutput;
        }

//        echo "<pre><code>{$artisanOutput}</code></pre>";

        return $artisanOutput;
    }

    public function deleteTenant(TenantObject|int|string|null $tenant)
    {
        if (!$tenant instanceof TenantObject) {
            $tenant = Tenancy::registry()->getTenant($tenant);
        }

        assert($tenant instanceof TenantObject, "Tenant not found");

        return $tenant->delete();
    }

}
