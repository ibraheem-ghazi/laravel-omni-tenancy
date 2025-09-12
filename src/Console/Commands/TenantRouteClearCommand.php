<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\TenancyRouter;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;

class TenantRouteClearCommand extends Command
{
    protected $name = 'tenant:route:clear';

    protected $description = 'Remove the tenancy related route cache file';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

     /**
     * Create a new route command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

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

        
        $this->clearRoutes($context->getCurrentTenant());
        
        return 0;
    }

    protected function clearRoutes(TenantObject $tenant)
    {
        $this->files->delete($this->laravel->getCachedRoutesPath());
        $this->components->info("Route cache cleared successfully for tenant [{$tenant->getId()} - {$tenant->getName()}].");
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
}
