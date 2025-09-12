<?php

namespace IbraheemGhazi\OmniTenancy\DTO;

use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Models\Tenant;
use IbraheemGhazi\OmniTenancy\Tenancy;
use IbraheemGhazi\OmniTenancy\Events\TenantDeletingEvent;
use IbraheemGhazi\OmniTenancy\Events\TenantDeletedEvent;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\File;

class DatabaseTenantObject implements TenantObject
{

    /**
     * @var \Closure|array
     */
    public static \Closure|array $computeHash = [self::class, 'computeHash'];
    
    public function __construct(protected Tenant $tenant)
    {
    }

    public static function makeFromModel(?Tenant $tenant): ?static
    {
        if(!$tenant){
            return null;
        }
        return new static($tenant);
    }

    public function getId(): string|int
    {
        return $this->tenant->getKey();
    }

    public function getHash(): string
    {
        if(is_callable(static::$computeHash)){
            return call_user_func(static::$computeHash, $this);
        }
        return static::computeHash($this);
    }

    protected static function computeHash(self $tenant)
    {
        return crc32($tenant->getId());
    }

    public function getName(): string
    {
        return $this->tenant->getAttribute('name');
    }

    public function getRegisteredMainDomain(): ?string
    {
        return $this->tenant->mainDomain?->getAttribute('domain') ?: null;
    }

    public function getMainDomain(): ?string
    {
        return TenantIdentifier::guessTenantDomain($this);
    }

    public function getDatabaseName(): ?string
    {
        return $this->getEncryptedOption('db.name');
    }

    public function getDatabaseUser(): ?string
    {
        return $this->getEncryptedOption('db.user');
    }

    public function getDatabasePassword(): ?string
    {
        return $this->getEncryptedOption('db.pass');
    }

    public function isActive(): bool
    {
        return $this->tenant->getAttribute('active');
    }

    public function setActive(bool $isActive): static
    {
        $this->tenant->fill(['active' => $isActive])->save();
        return $this;
    }

    public function getOwnerInfo(string $key = null, mixed $default = ''): mixed
    {
        return data_get($this->tenant->owner_info, $key, $default);
    }

    public function setOwnerInfo(string $key, mixed $value, bool $save = true): static
    {
        $ownerInfo = $this->tenant->owner_info;
        if (!is_array($ownerInfo)) {
            $ownerInfo = [];
        }
        data_set($ownerInfo, $key, $value);
        $this->tenant->owner_info = $ownerInfo;
        if ($save) $this->tenant->save();
        return $this;
    }

    public function setMultipleOwnerInfo(array $input, bool $save = true): static
    {
        foreach($input as $key => $value)
        {
            $this->setOwnerInfo($key, $value, false);
        }
        if ($save) $this->tenant->save();
        return $this;
    }

    public function getOption(string $key, mixed $default = ''): mixed
    {
        return data_get($this->tenant->options, $key, $default);
    }

    public function setOption(string $key, mixed $value, bool $save = true): static
    {
        $options = $this->tenant->options;
        if (!is_array($options)) {
            $options = [];
        }
        data_set($options, $key, $value);
        $this->tenant->options = $options;
        if ($save) $this->tenant->save();
        return $this;
    }

    public function getEncryptedOption(string $key, mixed $default = ''): mixed
    {
        $encryptedValue = $this->getOption($key, null);
        if(!filled($encryptedValue)){
            return $encryptedValue;
        }
        try{
            return decrypt($encryptedValue) ?: $default;
        }catch (DecryptException){
            return $default;
        }
    }

    public function setEncryptedOption(string $key, mixed $value, bool $save = true): mixed
    {
        return $this->setOption($key, filled($value) ? encrypt($value) : '', $save);
    }

    public function getGroups(): array
    {
        $groups = $this->getOption('routes.group', []);
        return is_array($groups) ? $groups : [];
    }

    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->getGroups());
    }

    public function addGroup(string $group): static
    {

        $currentGroups = $this->getGroups();
        $this->setOption('routes.group', array_unique([
            ...$currentGroups,
            $group
        ]));
        return $this;
    }

    public function removeGroup(string $group): static
    {
        $this->setOption('routes.group', array_filter($this->getGroups(), fn($entry) => $entry !== $group));
        return $this;
    }

    public function getMaintenanceModeData(): array
    {
        return [
            'enabled' => boolval($this->getOption('maintenance.enabled', false)),
            'secret' => $this->getEncryptedOption('maintenance.secret', null),
            'template' => $this->getOption('maintenance.template', null),
            'redirect' => $this->getOption('maintenance.redirect', null),
            'status' => $this->getOption('maintenance.status', null),
            'retry' => $this->getOption('maintenance.retry', null),
            'refresh' => $this->getOption('maintenance.refresh', null),
        ];
    }

    public function setMaintenanceMode(bool $enable, true|string|null $secret = true, ?string $template = null, ?string $redirect = null, ?int $status = null, ?int $retry = null, ?int $refresh = null): static
    {
        $this->setOption('maintenance.enabled', $enable);
        if($enable){
            $this->setEncryptedOption('maintenance.secret', $secret);
            $this->setOption('maintenance.template', $template);
            $this->setOption('maintenance.redirect', $redirect);
            $this->setOption('maintenance.status', $status);
            $this->setOption('maintenance.retry', $retry);
            $this->setOption('maintenance.refresh', $refresh);
        } else{
            $this->setOption('maintenance.secret', null);
            $this->setOption('maintenance.template', null);
            $this->setOption('maintenance.redirect', null);
            $this->setOption('maintenance.retry', null);
            $this->setOption('maintenance.refresh', null);
        }
        return $this;
    }

    public function delete()
    {
        if(Tenancy::context()->getCurrentTenant()?->getId() === $this->getId()){
            throw new \RuntimeException("Selected tenant is currently active in current context, therefore it can not be deleted");
        }

        TenantDeletingEvent::dispatch($this);
      
        $storagePublicDirPath = storage_path("app/public/" . crc32($this->getId()));
        if (File::isDirectory($storagePublicDirPath)) {
            File::deleteDirectory($storagePublicDirPath);
        } 

        $backupsDirPath = storage_path("app/backups/tenant-{$this->getId()}");
        if (File::isDirectory($backupsDirPath)) {
            File::deleteDirectory($backupsDirPath);
        } 

        try{
            if(filled($this->getDatabaseName()))
                Tenancy::databaseManager()->dropDatabase($this->getDatabaseName());
        }catch(\RuntimeException){}
        try{
            if(filled($this->getDatabaseUser()))
                Tenancy::databaseManager()->dropUser($this->getDatabaseUser());
        }catch(\RuntimeException){}

        $this->tenant->domains()->delete();
        $this->tenant->delete();

        TenantDeletedEvent::dispatch($this);
    }

}
