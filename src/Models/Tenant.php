<?php

namespace IbraheemGhazi\OmniTenancy\Models;


use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Models\Traits\HasCentralConnection;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read ?Domain $mainDomain
 * @property-read ?Collection<Domain> $domains
 * @property ?array $options
 * @property ?array $owner_info
 */
class Tenant extends Model
{
    use HasCentralConnection;

    protected $fillable = [
        'name',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'options' => 'json',
            'owner_info' => 'json',
        ];
    }

    /**
     * @return HasMany|Tenant
     */
    public function domains(): HasMany|Tenant
    {
        return $this->hasMany(Domain::class);
    }

    /**
     *
     * @return Tenant|HasOne
     * @noinspection PhpUnused used as relation property
     */
    public function mainDomain(): HasOne|Tenant
    {
        return $this->hasOne(Domain::class)->where('is_main', true);
    }

    public function createMainDomain(string $domain)
    {
        return $this->domains()->create([
            'domain' => $domain,
            'is_main' => 1
        ]);
    }
}

