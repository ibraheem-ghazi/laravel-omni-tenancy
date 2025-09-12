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

class TenantInitCommand extends Command
{
    protected $signature = 'tenant:init';

    protected $description = 'Initialize what needed for tenancy to work';

    public function handle(): int
    {
        $this->migrateCentralConnectionDatabase();
        $this->createFirstTenant();
        $this->createManagerTenant();
        $this->prepareRoutesFolder();
        return 0;
    }

    protected function createFirstTenant()
    {
        $registry = Tenancy::registry();
        $tenant = $registry->getTenant(1);
        if($tenant){
            $this->info("Central Tenant found with ID <fg=yellow>\"{$tenant->getId()}\"</>, ensure to update config of domain identifier with this ID");
            return;
        }

        $tenant = $registry->getTenantByColumn('name', 'Central');
        if($tenant){
            $this->info("Central Tenant found with ID <fg=yellow>\"{$tenant->getId()}\"</>, ensure to update config of domain identifier with this ID");
            return;
        }

        $tenant = Tenancy::newCreator()
            ->withName('Central')
            ->withRoutesGroups(['central'])
            ->withOwnerInfo([
                'name' => 'Auto Created',
            ])
            ->createTenant();

        $this->info("Central Tenant created with ID <fg=yellow>\"{$tenant->getId()}\"</>, ensure to update config of domain identifier with this ID");
    }

     protected function createManagerTenant()
    {
        $registry = Tenancy::registry();
        $tenant = $registry->getTenant(2);
        if($tenant){
            $this->info("Manager Tenant found with ID <fg=yellow>\"{$tenant->getId()}\"</>, ensure to update config of domain identifier with this ID");
            return;
        }

        $tenant = $registry->getTenantByColumn('name', 'Manager');
        if($tenant){
            $this->info("Manager Tenant found with ID <fg=yellow>\"{$tenant->getId()}\"</>, ensure to update config of domain identifier with this ID");
            return;
        }

        $tenant = Tenancy::newCreator()
            ->withName('Manager')
            ->withRoutesGroups(['manager'])
            ->withOwnerInfo([
                'name' => 'Auto Created',
            ])
            ->createTenant();

        $this->info("Manager Tenant created with ID <fg=yellow>\"{$tenant->getId()}\"</>, ensure to update config of domain identifier with this ID");
    }

    private function prepareRoutesFolder()
    {
        $anyCreated = false;
        foreach([
            'routes/tenancy/central'
        ] as $folder)
        {
            if(!file_exists($folder)){
                mkdir(base_path($folder), 0755, true);
                touch("$folder/web.php");
                $anyCreated = true;
            }
        }

        if($anyCreated)
            $this->info("routes group routing folders has been prepared under path \"routes/tenancy\"");
        else
            $this->info("[SKIP] routes group routing folders already exists under path \"routes/tenancy\"");
    }

    private function migrateCentralConnectionDatabase()
    {
        $this->call('migrate', [
            '--step' => true,
            '--realpath' => true,
            '--database' => config('tenancy.connections.central', config('database.default')),
            '--path' => [
                __DIR__ . '/../../Database',
                base_path('database/migrations')

            ]
        ]);
    }
}
