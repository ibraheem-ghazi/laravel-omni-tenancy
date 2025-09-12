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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TenantSeedCommand extends Command
{
    protected $name = 'tenant:seed';

    protected $description = 'Seed the database for the selected tenant based on tenancy.php config';

    public function handle(): int
    {
         if (!app()->bound(TenantContext::class)) {
            $this->error('TenantContext is not bound. This command should be run through tenant:run.');
            return 1;
        }

        $context = Tenancy::context();

        if (!$context->hasTenant()) {
            if(!$this->argument('tenantId')){
                $this->error('No tenant context active. This command should be run through tenant:run or at least pass tenentId argument.');
                return 1;
            }
            $context->setTenant($this->argument('tenantId'));
        }

        $tenant = $context->getCurrentTenant();

        if(filled($this->option('class'))){
            Tenancy::newCreator()::callSeeder($tenant, $this->option('class'));
        }else{
            Tenancy::newCreator()::seedDatabase($tenant);
        }
        return 0;
    }

     /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['tenantId', InputArgument::OPTIONAL, 'the tenant ID, you pass it or run command through tenant:run'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'Execute specific seeder class'],
        ];
    }
}
