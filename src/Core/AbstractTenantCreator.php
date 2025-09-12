<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use IbraheemGhazi\OmniTenancy\Contracts\TenantCreator;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Random\RandomException;
use IbraheemGhazi\OmniTenancy\Models\TenantRequest;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantCreationFailedException;

abstract class AbstractTenantCreator implements TenantCreator
{
    use Macroable;
    protected array $attributes = [];
    protected bool $keep_request_after_create_tenant = false;
    protected bool $with_routes_cache = false;
    public static $failBehaviour = [self::class, 'onFailBehaviour'];

    public function __construct()
    {
        $this->setActive(true);
        $this->withOptions([]);
    }

    public static function make(): static
    {
        return new static();
    }

    public function fromRequest(int|string|TenantRequest $tenantRequest): static
    {
        // Implement fromRequest() method.
        return $this;
    }

    public function keepRequestAfterCreateTenant(bool $value = true): static
    {
        $this->keep_request_after_create_tenant = $value;
        return $this;
    }

    public function withRoutesCache(bool $value = true): static
    {
        $this->with_routes_cache = $value;
        return $this;
    }

    public final function withName(string $name): static
    {
        data_set($this->attributes, 'name', $name);
        return $this;
    }

    public final function setActive(bool $active): static
    {
        data_set($this->attributes, 'active', $active);
        return $this;
    }

    public final function withDomain(string $domain): static
    {
        data_set($this->attributes, 'domain', $domain);
        return $this;
    }

    public function withSubDomain(string $subDomain): static
    {
        return $this->withDomain(TenantIdentifier::domain($subDomain));
    }

    /**
     * @throws RandomException
     */
    public function withDatabase(): static
    {
        $prefix = config('tenancy.database.prefix', '');
        return $this->withCustomDatabase(
            $prefix . bin2hex(random_bytes(8)),
            $prefix . bin2hex(random_bytes(8)),
            Str::random(42),
        );
    }

    public final function withCustomDatabase(string $database, string $username = null, string $password = null): static
    {
        data_set($this->attributes, 'db.name', $database);
        data_set($this->attributes, 'db.user', $username);
        data_set($this->attributes, 'db.pass', $password);
        return $this;
    }

    public final function withRoutesGroups(array|Collection $groups): static
    {
        if(is_a($groups, Collection::class)) {
            $groups = $groups->toArray();
        }
        data_set($this->attributes, 'routes.group', $groups);
        return $this;
    }

    public function withOptions(array $options): static
    {
        data_set($this->attributes, 'options', $options);
        return $this;
    }

    public final function withOwnerInfo(array|Collection $ownerInfo): static
    {
        if(is_a($ownerInfo, Collection::class)) {
            $ownerInfo = $ownerInfo->toArray();
        }
        data_set($this->attributes, 'owner_info', $ownerInfo);
        return $this;
    }

    protected function abortAndFail(?string $message)
    {
        if(is_callable(static::$failBehaviour)){
            return call_user_func(static::$failBehaviour, $message);
        }
        throw new TenantCreationFailedException($message);
    }

    protected function onFailBehaviour(?string $message)
    {
        throw new TenantCreationFailedException($message);
    }

    abstract public function createRequest();
    abstract public function createTenant(): ?TenantObject;
    abstract public function deleteTenant(TenantObject|int|string|null $tenant);

    abstract public function hasAnyWithDomain(string $domain, bool $includeMapped = true): bool;
    abstract public function hasTenantWithDomain(string $domain, bool $includeMapped = true): bool;
    abstract public function hasRequestWithDomain(string $domain, bool $includeMapped = true): bool;
}
