<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TenantListCommand extends Command
{
    protected $signature = 'tenant:list {--active-only : Only process active tenants}';

    protected $description = 'List All tenants with all information';

    public function handle(): int
    {
        $registry = Tenancy::registry();

        return $this->listTenants($registry);
    }

    protected function listTenants(TenantRegistry $registry): int
    {
        $tenants = $this->fetchTenantsWithProgress($registry, $this->option('active-only'));

        $this->table(
            ['ID', 'Name', 'Domain', 'Database', 'Routes Groups', 'Status'],
            $tenants->map(fn(TenantObject $t) => [
                $t->getId(),
                $t->getName(),
                $t->getDatabaseName(),
                $t->getMainDomain(),
                implode(', ', $t->getGroups()),
                $t->isActive() ? '✅ Active' : '❌ Inactive'
            ])
        );

        return self::SUCCESS;
    }

    protected function fetchTenantsWithProgress(TenantRegistry $registry, bool $activeOnly = false): Collection
    {
        $this->info('Loading tenants...');
        $progress = $this->output->createProgressBar();
        $progress->start();

        $tenants = $registry->getAllTenantsWithProgress(
            fn() => $progress->advance(),
            $activeOnly
        );

        $progress->finish();
        $this->newLine();

        return $tenants;
    }

}
