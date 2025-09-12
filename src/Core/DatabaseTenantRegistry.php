<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use IbraheemGhazi\OmniTenancy\Contracts\TenantCreator;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\DTO\DatabaseTenantObject;
use IbraheemGhazi\OmniTenancy\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Macroable;

class DatabaseTenantRegistry implements TenantRegistry
{
    use Macroable;

    public static bool $cacheEnabled = false;
    protected int $cacheTtl = 3600;

    protected array $columns = ['id', 'name', 'active', 'options', 'owner_info', 'created_at', 'updated_at'];

    public function __construct()
    {
        static::$cacheEnabled = config('tenancy.registry.enable_cache', false);
    }

    public function getTenant(string $id): ?TenantObject
    {
        return $this->getTenantByColumn('id', $id);
    }

    public function getTenantByColumn(string $column, mixed $value): ?TenantObject
    {
        return $this->cache("tenant.$column.".crc32($value), function() use ($column, $value) {
            $model = Tenant::query()->select($this->columns)
                ->where($column, $value)
                ->first();
            return DatabaseTenantObject::makeFromModel($model);
        });
    }

    public function getTenantByDomain(string $domain): ?TenantObject
    {
        return $this->cache("tenant.domains.$domain.", function() use ($domain) {
            $model = Tenant::query()->select($this->columns)
                ->whereHas('domains', function ($query) use ($domain) {
                    $query->where('domain', $domain);
                })
                ->first();
            return DatabaseTenantObject::makeFromModel($model);
        });
    }

    public function existsTenantByDomain(string $domain): bool
    {
        return $this->cache("tenant.domains.$domain.exists", function() use ($domain) {
            return Tenant::query()
                ->whereHas('domains', function ($query) use ($domain) {
                    $query->where('domain', $domain);
                })
                ->exists();
        });
    }

    public function existsTenantByName(string $name): bool
    {
        return $this->cache("tenant.names.$name.exists", function() use ($name) {
            return Tenant::query()->where('name', $name)->exists();
        });
    }

    public function getAllTenants(): Collection
    {
        return $this->cache('tenants.all', function() {
            return Tenant::select($this->columns)
                ->orderBy('id')->orderBy('name')
                ->get()->map(fn(Tenant $model)=>DatabaseTenantObject::makeFromModel($model));
        });
    }

    public function getAllTenantsPaginated(int $page = 1, ?int $perpage = null): LengthAwarePaginator
    {
        return $this->cache('tenants.all.paginated.' . $page, function() use($page, $perpage) {
            $items = Tenant::select($this->columns)
                ->orderBy('id')->orderBy('name')
                ->paginate($perpage, ['*'], null, $page);
            return new LengthAwarePaginator(
                $items->map(fn(Tenant $model)=>DatabaseTenantObject::makeFromModel($model)),
                $items->total(),
                $items->perPage(),
                $items->currentPage(),
                $items->getOptions()
            );
        });
    }


    public function getAllTenantsWithProgress(callable $progressCallback, bool $activeOnly = false): Collection
    {
        $cacheKey = 'tenants.progress.' . ($activeOnly ? 'active' : 'all');

        return $this->cache($cacheKey, function() use ($progressCallback, $activeOnly) {
            $query = Tenant::select($this->columns)
                ->orderBy('id')->orderBy('name');

            if ($activeOnly) {
                $query->where('active', true);
            }

            $tenants = collect();

            $query->chunk(100, function($chunk) use (&$tenants, $progressCallback) {
                foreach ($chunk as $tenant) {
                    $tenants->push(DatabaseTenantObject::makeFromModel($tenant));
                    $progressCallback();
                }
            });

            return $tenants;
        });
    }

    public function forEachTenant(int $perPage, callable $callback, bool $activeOnly = false): void
    {
        $query = Tenant::select($this->columns)
            ->orderBy('id');

        if ($activeOnly) {
            $query->where('active', true);
        }

        $query->chunk($perPage, function ($tenants) use ($callback) {
            foreach ($tenants as $tenant) {
                $callback(DatabaseTenantObject::makeFromModel($tenant));
            }
        });
    }

    public function getTenantIds(): array
    {
        return $this->cache('tenants.ids', function() {
            return Tenant::query()
                ->pluck('id')
                ->toArray();
        });
    }

    public function exists(string $id): bool
    {
        return $this->cache("tenant.exists.$id", function() use ($id) {
            return Tenant::query()->where('id', $id)
                ->exists();
        });
    }

    public function isActive(string $id): bool
    {
        return $this->cache("tenant.active.$id", function() use ($id) {
            return Tenant::where('id', $id)
                ->where('active', true)
                ->exists();
        });
    }

    public function newCreator(): TenantCreator
    {
        return DatabaseTenantCreator::make();
    }

    public function cache($key, callable $callback): mixed
    {
        if(!static::$cacheEnabled){
            return $callback();
        }
        return Cache::remember($key, $this->cacheTtl, $callback);
    }
}
