<?php

namespace IbraheemGhazi\OmniTenancy\Contracts;

//todo delete the tenant object

interface TenantObject
{

    public function getId(): string|int;
    public function getHash(): string;

    public function getName(): string;
    public function getRegisteredMainDomain(): ?string;
    public function getMainDomain(): ?string;

    public function getDatabaseName(): ?string;
    public function getDatabaseUser(): ?string;
    public function getDatabasePassword(): ?string;

    public function isActive(): bool;

    public function setActive(bool $isActive): static;

    public function getOwnerInfo(string $key = null, mixed $default = ''): mixed;
    public function setOwnerInfo(string $key, mixed $value, bool $save = true): static;
    public function setMultipleOwnerInfo(array $input, bool $save = true): static;

    public function getOption(string $key, mixed $default = ''): mixed;
    public function setOption(string $key, mixed $value, bool $save = true): static;
    public function getEncryptedOption(string $key, mixed $default = ''): mixed;
    public function setEncryptedOption(string $key, mixed $value, bool $save = true): mixed;

    public function getGroups(): array;
    public function hasGroup(string $group): bool;
    public function addGroup(string $group): static;
    public function removeGroup(string $group): static;

    public function getMaintenanceModeData(): array;

    public function setMaintenanceMode(bool $enable, true|string|null $secret = true, ?string $template = null, ?string $redirect = null, ?int $status = null, ?int $retry = null, ?int $refresh = null): static;

    public function delete();

}
