<?php

namespace IbraheemGhazi\OmniTenancy\Core\Databases;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Support\Traits\Macroable;

abstract class AbstractTenantDatabaseManager
{
    use Macroable;

    public final function __construct(private ?TenantObject $tenant)
    {
    }

    public static final function make(?TenantObject $tenant): static
    {
        return new static($tenant);
    }

    public static function makeForCurrentTenant()
    {
        return static::make(Tenancy::context()->getCurrentTenant());
    }

    protected final function getTenant(): ?TenantObject
    {
        return $this->tenant;
    }

    abstract public function createDatabase(string $name): bool;
    abstract public function dropDatabase(string $name): bool;
    abstract public function renameDatabase(string $name, string $new_name): bool;
    abstract public function backupDatabase(string $dbName, string $saveAt): bool;
    abstract public function restoreDatabase(string $dbName, string $sourceSqlFile): bool;
    abstract public function createUser(string $username, string $password, string $database): bool;
    abstract public function dropUser(string $username): bool;

    protected function getDatabaseUsername()
    {
        $username = $this->tenant?->getDatabaseUser();
        if(!filled($username)){
            $connection = config('tenancy.connections.tenants');
            return config("database.connections.$connection.username");
        }
        return $username;
    }
    protected function getDatabasePassword()
    {
        $username = $this->tenant?->getDatabasePassword();
        if(!filled($username)){
            $connection = config('tenancy.connections.tenants');
            return config("database.connections.$connection.password");
        }
        return $username;
    }
}
