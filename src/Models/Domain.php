<?php

namespace IbraheemGhazi\OmniTenancy\Models;

use IbraheemGhazi\OmniTenancy\Models\Traits\HasCentralConnection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $domain
 * @property bool $is_main
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read bool $is_subdomain
 * @property-read Tenant $tenant
 */
class Domain extends Model
{
    use HasCentralConnection;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_main' => 'boolean',
        'tenant_id' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = ['is_subdomain'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'domain',
        'is_main',
        'tenant_id',
    ];

    public function getConnectionName()
    {
        return config('tenancy.connections.central');
    }

    /**
     * Get the tenant that owns the domain.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Activate the current domain by setting it as the main domain.
     *
     * @return static
     */
    public function activate(): static
    {
        $this->tenant->domains()->update(['is_main' => false]);
        $this->update(['is_main' => true]);
        return $this;
    }

    /**
     * Check if the current domain is a subdomain.
     *
     * @return bool
     */
    public function isCurrentSubdomain(): bool
    {
        return self::checkIsSubdomain($this->domain);
    }

    /**
     * Define the is_subdomain accessor.
     *
     * @return Attribute
     * @noinspection PhpUnused used as $append
     */
    protected function isSubdomain(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->isCurrentSubdomain()
        );
    }

    /**
     * Check if the given domain string is a subdomain.
     *
     * @param  string  $domain
     * @return bool
     */
    public static function checkIsSubdomain(string $domain): bool
    {
        return str_contains($domain, '.');
    }

    /**
     * Scope a query to only include main domains.
     *
     * @param Builder $query
     * @return Builder
     * @noinspection PhpUnused
     */
    public function scopeMain(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    /**
     * Scope a query to only include domains for a specific tenant.
     *
     * @param Builder $query
     * @param  int  $tenantId
     * @return Builder
     * @noinspection PhpUnused
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
