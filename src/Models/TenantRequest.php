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
 * @property string $name
 * @property string $domain
 * @property ?array $routes_group
 * @property ?string $db_namd
 * @property ?string $db_user
 * @property ?string $db_pass
 * @property ?array $options
 * @property ?array $owner_info
 */
class TenantRequest extends Model
{
    use HasCentralConnection;

    protected $table = 'tenants_requests';

    protected $fillable = [
        'name',
        'domain',
        'routes_group',
        'db_name',
        'db_user',
        'db_pass',
        'options',
        'owner_info',
    ];

    protected function casts(): array
    {
        return [
            'routes_group' => 'array',
            'owner_info' => 'json',
            'options' => 'json',
        ];
    }
}

